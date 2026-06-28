"""
prediksibaku.py — Prediksi Penggunaan Bahan Baku — Prophet
W9 Cafe POS | Capstone STIE Totalwin

Mengikuti notebook: 1 Model Bahan Baku Prophet preprocessing_prediction.ipynb
  - Preprocessing: parse Tanggal, drop NaN/duplikat, agregasi harian,
    reindex tanggal, IQR Capping per bahan baku
  - Split 75:25
  - Prophet: yearly=False, weekly=True, additive,
    changepoint_prior_scale=0.07, seasonality_prior_scale=8
  - Regressor: is_weekend (1 = Sabtu/Minggu)
  - Evaluasi: MAE, RMSE, MAPE, SMAPE pada data test (25%)
  - Prediksi 2 hari ke depan per bahan baku
  - Visualisasi: forecast_all, feature_importance, evaluation 2×2,
    all_items grid, per_ingredient individual

Input DataFrame kolom: Tanggal, Bahan_Baku, Unit, Jumlah_Digunakan
Output: dict JSON sesuai kontrak PrediksiBahanBaku.php + prediksi-bahan-baku.blade.php
"""

import io, base64, warnings, math
import numpy as np
import pandas as pd
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
import matplotlib.dates as mdates
from prophet import Prophet
from sklearn.metrics import mean_absolute_error, mean_squared_error

warnings.filterwarnings("ignore")

HARI_ID = {
    "Monday":    "Senin",
    "Tuesday":   "Selasa",
    "Wednesday": "Rabu",
    "Thursday":  "Kamis",
    "Friday":    "Jumat",
    "Saturday":  "Sabtu",
    "Sunday":    "Minggu",
}

plt.rcParams.update({
    "font.family":       "DejaVu Sans",
    "axes.spines.top":   False,
    "axes.spines.right": False,
    "axes.titlesize":    12,
    "axes.titleweight":  "bold",
    "axes.titlepad":     12,
    "axes.labelsize":    10,
    "axes.labelpad":     6,
    "xtick.labelsize":   8.5,
    "ytick.labelsize":   8.5,
    "legend.fontsize":   8,
    "figure.facecolor":  "white",
    "axes.facecolor":    "#fafafa",
    "axes.grid":         True,
    "grid.color":        "#e5e7eb",
    "grid.linewidth":    0.65,
})


# ─────────────────────────────────────────────────────────────────────────────
# Helpers
# ─────────────────────────────────────────────────────────────────────────────

def _fig_to_b64(fig) -> str:
    buf = io.BytesIO()
    fig.savefig(buf, format="png", bbox_inches="tight", dpi=110)
    buf.seek(0)
    b64 = base64.b64encode(buf.read()).decode()
    plt.close(fig)
    return b64


def _smape(y_true, y_pred) -> float:
    y_true, y_pred = np.array(y_true), np.array(y_pred)
    denom = (np.abs(y_true) + np.abs(y_pred)) / 2
    mask  = denom != 0
    return float(np.mean(np.abs(y_true[mask] - y_pred[mask]) / denom[mask]) * 100) if mask.sum() else 0.0


def _mape(y_true, y_pred) -> float:
    y_true, y_pred = np.array(y_true), np.array(y_pred)
    mask = y_true != 0
    return float(np.mean(np.abs((y_true[mask] - y_pred[mask]) / y_true[mask])) * 100) if mask.sum() else 0.0


# ─────────────────────────────────────────────────────────────────────────────
# TAHAP 1 — PREPROCESSING  (sel 2–19 notebook)
# ─────────────────────────────────────────────────────────────────────────────

def _preprocess(df: pd.DataFrame):
    logs = []

    # Parse tanggal
    df = df.copy()
    df["Tanggal"] = pd.to_datetime(df["Tanggal"])

    # Simpan peta satuan per bahan baku
    unit_map: dict = df.groupby("Bahan_Baku")["Unit"].first().to_dict()

    # Agregasi harian per bahan baku
    df = df.sort_values("Tanggal")
    df_agg = df.groupby(["Tanggal", "Bahan_Baku"], as_index=False)["Jumlah_Digunakan"].sum()
    df_agg["Day_Type"] = df_agg["Tanggal"].dt.dayofweek.apply(
        lambda x: "Weekend" if x >= 5 else "Weekday"
    )
    logs.append({
        "tahap":  "Agregasi Harian",
        "detail": (
            f"Diagregasi per Tanggal×Bahan_Baku. "
            f"Baris: {len(df_agg)}, bahan baku unik: {df_agg['Bahan_Baku'].nunique()}."
        ),
    })

    # Reindex — lengkapi tanggal yang hilang dengan Jumlah=0
    min_date = df_agg["Tanggal"].min()
    max_date = df_agg["Tanggal"].max()
    full_dates = pd.date_range(start=min_date, end=max_date, freq="D")
    parts = []
    for bahan in df_agg["Bahan_Baku"].unique():
        tmp = df_agg[df_agg["Bahan_Baku"] == bahan].set_index("Tanggal")
        tmp = tmp.reindex(full_dates)
        tmp["Bahan_Baku"]      = bahan
        tmp["Jumlah_Digunakan"] = tmp["Jumlah_Digunakan"].fillna(0)
        tmp = tmp.reset_index().rename(columns={"index": "Tanggal"})
        parts.append(tmp)
    df_full = pd.concat(parts, ignore_index=True)
    df_full["Day_Type"] = df_full["Tanggal"].dt.dayofweek.apply(
        lambda x: "Weekend" if x >= 5 else "Weekday"
    )
    logs.append({
        "tahap":  "Lengkapi Tanggal Kosong",
        "detail": f"Tanggal hilang diisi Jumlah=0. Total baris: {len(df_full)}.",
    })

    # IQR Capping per bahan baku
    capped, n_outlier = [], 0
    for bahan in df_full["Bahan_Baku"].unique():
        tmp = df_full[df_full["Bahan_Baku"] == bahan].copy()
        Q1, Q3 = tmp["Jumlah_Digunakan"].quantile(0.25), tmp["Jumlah_Digunakan"].quantile(0.75)
        IQR = Q3 - Q1
        lo, hi = Q1 - 1.5 * IQR, Q3 + 1.5 * IQR
        n_outlier += int(((tmp["Jumlah_Digunakan"] < lo) | (tmp["Jumlah_Digunakan"] > hi)).sum())
        tmp["Jumlah_Digunakan"] = tmp["Jumlah_Digunakan"].clip(lower=lo, upper=hi)
        capped.append(tmp)
    df_capped = pd.concat(capped, ignore_index=True)
    logs.append({
        "tahap":  "Outlier IQR Capping",
        "detail": f"Total nilai outlier di-cap: {n_outlier} baris.",
    })

    return df_capped, unit_map, min_date, max_date, logs


# ─────────────────────────────────────────────────────────────────────────────
# TAHAP 2 — PROPHET MODEL + PREDIKSI  (sel 20–33 notebook)
# ─────────────────────────────────────────────────────────────────────────────

def run_prediction_pipeline_bahan_baku(df: pd.DataFrame) -> dict:
    df_capped, unit_map, min_date, max_date, logs = _preprocess(df)

    ingredients = df_capped["Bahan_Baku"].unique().tolist()
    n = len(ingredients)

    predictions_out       = []
    summary_rows          = []
    per_ingredient_charts = []
    all_items_store       = []   # untuk chart gabungan

    for bahan in ingredients:
        df_b = df_capped[df_capped["Bahan_Baku"] == bahan].sort_values("Tanggal").reset_index(drop=True)

        # Siapkan DataFrame Prophet (ds, y, is_weekend)
        df_p = df_b[["Tanggal", "Jumlah_Digunakan"]].rename(
            columns={"Tanggal": "ds", "Jumlah_Digunakan": "y"}
        ).copy()
        df_p["is_weekend"] = (df_p["ds"].dt.dayofweek >= 5).astype(int)

        # Split 75 : 25
        n_rows  = len(df_p)
        n_train = math.ceil(n_rows * 0.75)
        train   = df_p.iloc[:n_train].reset_index(drop=True)
        test    = df_p.iloc[n_train:].reset_index(drop=True)

        # ── Prophet — konfigurasi notebook Bahan Baku ──
        model = Prophet(
            yearly_seasonality=False,
            weekly_seasonality=True,
            daily_seasonality=False,
            seasonality_mode="additive",
            interval_width=0.95,
            changepoint_prior_scale=0.07,
            seasonality_prior_scale=8,
        )
        model.add_regressor("is_weekend")
        model.fit(train[["ds", "y", "is_weekend"]])

        # Evaluasi pada test set
        test_fc = model.predict(test[["ds", "is_weekend"]])
        y_true  = test["y"].values
        y_pred  = test_fc["yhat"].clip(lower=0).values

        mae_v   = float(mean_absolute_error(y_true, y_pred))
        rmse_v  = float(math.sqrt(mean_squared_error(y_true, y_pred)))
        mape_v  = _mape(y_true, y_pred)
        smape_v = _smape(y_true, y_pred)

        # Prediksi 2 hari ke depan
        last_date    = df_p["ds"].max()
        future_dates = pd.date_range(start=last_date + pd.Timedelta(days=1), periods=2, freq="D")
        future_df    = pd.DataFrame({"ds": future_dates})
        future_df["is_weekend"] = (future_df["ds"].dt.dayofweek >= 5).astype(int)
        future_fc    = model.predict(future_df)

        forecast_days = []
        for _, row in future_fc.iterrows():
            forecast_days.append({
                "tanggal":    str(row["ds"].date()),
                "hari":       HARI_ID.get(row["ds"].strftime("%A"), row["ds"].strftime("%A")),
                "day_type":   "Weekend" if row["ds"].dayofweek >= 5 else "Weekday",
                "prediksi":   round(float(max(0.0, row["yhat"])), 1),
                "batas_bawah": round(float(max(0.0, row["yhat_lower"])), 1),
                "batas_atas":  round(float(max(0.0, row["yhat_upper"])), 1),
            })

        total_fc = sum(d["prediksi"] for d in forecast_days)
        satuan   = unit_map.get(bahan, "")

        predictions_out.append({
            "nama_bahan_baku": bahan,
            "satuan":          satuan,
            "mae":             round(mae_v,   2),
            "rmse":            round(rmse_v,  2),
            "mape":            round(mape_v,  2),
            "smape":           round(smape_v, 2),
            "total_forecast":  round(total_fc, 1),
            "forecast":        forecast_days,
        })

        summary_rows.append({
            "nama_bahan_baku": bahan,
            "satuan":          satuan,
            "total_forecast":  round(total_fc, 1),
            "avg_per_day":     round(total_fc / 2, 1),
            "mae":             round(mae_v,   2),
            "rmse":            round(rmse_v,  2),
            "mape":            round(mape_v,  2),
            "smape":           round(smape_v, 2),
            "model":           "Prophet",
        })

        # ── Grafik individual per bahan baku ──────────────────────────
        fig, ax = plt.subplots(figsize=(14, 3.8))

        ax.plot(train["ds"], train["y"],
                color="#93c5fd", linewidth=1.0, alpha=0.8, label="Training Aktual")
        ax.plot(test["ds"],  y_true,
                color="#059669", linewidth=1.4, marker="o", markersize=3, label="Test Aktual")
        ax.plot(test["ds"],  y_pred,
                color="#f97316", linewidth=1.4, linestyle="--", label="Test Prediksi")

        if len(test) > 0:
            ax.fill_between(
                test_fc["ds"],
                test_fc["yhat_lower"].clip(lower=0),
                test_fc["yhat_upper"].clip(lower=0),
                alpha=0.14, color="#f97316", label="CI 95%",
            )

        # Weekend shading
        for dt in test["ds"]:
            if dt.dayofweek >= 5:
                ax.axvspan(dt - pd.Timedelta(hours=12), dt + pd.Timedelta(hours=12),
                           alpha=0.07, color="#fbbf24")

        # Garis pemisah train/test
        if len(test) > 0:
            ax.axvline(test["ds"].iloc[0], color="#6b7280", linestyle=":",
                       linewidth=1.1, alpha=0.7, label="Train | Test")

        ax.set_title(
            f"{bahan}  —  Prediksi vs Aktual  "
            f"| MAE={mae_v:.1f}  MAPE={mape_v:.1f}%  SMAPE={smape_v:.1f}%"
        )
        ax.set_xlabel("Tanggal")
        ax.set_ylabel(f"Jumlah ({satuan})" if satuan else "Jumlah")
        ax.legend(loc="upper left", ncol=2, fontsize=7.5)
        ax.xaxis.set_major_formatter(mdates.DateFormatter("%d %b %y"))
        ax.xaxis.set_major_locator(mdates.AutoDateLocator())
        plt.setp(ax.get_xticklabels(), rotation=28, ha="right")
        fig.tight_layout(pad=1.5)

        per_ingredient_charts.append({"nama": bahan, "chart": _fig_to_b64(fig)})

        all_items_store.append({
            "bahan":   bahan,
            "satuan":  satuan,
            "train":   (train["ds"].tolist(), train["y"].tolist()),
            "test_ds": test["ds"].tolist(),
            "test_y":  y_true.tolist(),
            "pred_y":  y_pred.tolist(),
        })

    # Sort summary by total_forecast desc
    summary_rows.sort(key=lambda x: x["total_forecast"], reverse=True)

    # ─────────────────────────────────────────────────────────────────────
    # GRAFIK 1 — forecast_all: bar total prediksi per bahan baku
    # ─────────────────────────────────────────────────────────────────────
    names_s  = [r["nama_bahan_baku"] for r in summary_rows]
    totals_s = [r["total_forecast"]  for r in summary_rows]
    palette  = ["#6366f1", "#8b5cf6", "#a78bfa", "#c4b5fd"]
    colors_s = [palette[i % len(palette)] for i in range(len(names_s))]

    fig_fa, ax_fa = plt.subplots(figsize=(max(10, n * 1.1), 5))
    bars_fa = ax_fa.bar(names_s, totals_s, color=colors_s, width=0.6, alpha=0.88, edgecolor="white")
    for bar, val in zip(bars_fa, totals_s):
        ax_fa.text(
            bar.get_x() + bar.get_width() / 2, bar.get_height() + max(totals_s, default=1) * 0.012,
            f"{val:.1f}", ha="center", va="bottom", fontsize=8.5, color="#374151",
        )
    ax_fa.set_title("Total Prediksi Penggunaan 2 Hari ke Depan per Bahan Baku", pad=14)
    ax_fa.set_xlabel("Bahan Baku", labelpad=8)
    ax_fa.set_ylabel("Jumlah Prediksi (unit/satuan)", labelpad=8)
    ax_fa.tick_params(axis="x", rotation=38)
    ax_fa.set_xticklabels(ax_fa.get_xticklabels(), ha="right")
    ax_fa.yaxis.grid(True); ax_fa.xaxis.grid(False)
    fig_fa.tight_layout(pad=2)
    chart_forecast_all = _fig_to_b64(fig_fa)

    # ─────────────────────────────────────────────────────────────────────
    # GRAFIK 2 — feature_importance: Weekday vs Weekend per bahan baku
    # ─────────────────────────────────────────────────────────────────────
    feat_rows = []
    for bahan in ingredients:
        tmp = df_capped[df_capped["Bahan_Baku"] == bahan]
        feat_rows.append({
            "bahan":   bahan,
            "Weekday": float(tmp[tmp["Day_Type"] == "Weekday"]["Jumlah_Digunakan"].mean()),
            "Weekend": float(tmp[tmp["Day_Type"] == "Weekend"]["Jumlah_Digunakan"].mean()),
        })
    df_feat = pd.DataFrame(feat_rows).set_index("bahan")

    fig_fi, ax_fi = plt.subplots(figsize=(max(10, n * 1.1), 5))
    x_pos = np.arange(len(df_feat))
    w = 0.38
    ax_fi.bar(x_pos - w / 2, df_feat["Weekday"], width=w, label="Weekday", color="#3b82f6", alpha=0.87)
    ax_fi.bar(x_pos + w / 2, df_feat["Weekend"], width=w, label="Weekend", color="#f59e0b", alpha=0.87)
    ax_fi.set_xticks(x_pos)
    ax_fi.set_xticklabels(df_feat.index, rotation=38, ha="right")
    ax_fi.set_title("Analisis Rata-rata Jumlah Penggunaan Bahan Baku: Weekday vs Weekend", pad=14)
    ax_fi.set_xlabel("Bahan Baku", labelpad=8)
    ax_fi.set_ylabel("Rata-rata Jumlah Digunakan", labelpad=8)
    ax_fi.legend(framealpha=0.9, edgecolor="#e5e7eb", fancybox=False)
    ax_fi.yaxis.grid(True); ax_fi.xaxis.grid(False)
    fig_fi.tight_layout(pad=2)
    chart_feature_importance = _fig_to_b64(fig_fi)

    # ─────────────────────────────────────────────────────────────────────
    # GRAFIK 3 — evaluation 2×2: MAE, RMSE, MAPE, SMAPE
    # ─────────────────────────────────────────────────────────────────────
    metrics    = ["MAE",      "RMSE",     "MAPE (%)", "SMAPE (%)"]
    metric_keys = ["mae",     "rmse",     "mape",     "smape"]
    ev_colors  = ["#3b82f6",  "#10b981",  "#f59e0b",  "#ef4444"]

    fig_ev, axes_ev = plt.subplots(2, 2, figsize=(14, 7))
    for ax_ev, label, key, col in zip(axes_ev.flat, metrics, metric_keys, ev_colors):
        vals_ev = [r[key] for r in summary_rows]
        bars_ev = ax_ev.bar(names_s, vals_ev, color=col, alpha=0.82, width=0.6)
        for bar, val in zip(bars_ev, vals_ev):
            ax_ev.text(
                bar.get_x() + bar.get_width() / 2,
                bar.get_height() + max(vals_ev, default=1) * 0.012,
                f"{val:.2f}", ha="center", va="bottom", fontsize=7.5, color="#374151",
            )
        ax_ev.set_title(f"Evaluasi {label}")
        ax_ev.set_ylabel(label)
        ax_ev.tick_params(axis="x", rotation=38)
        ax_ev.set_xticklabels(ax_ev.get_xticklabels(), ha="right", fontsize=8)
        ax_ev.yaxis.grid(True); ax_ev.xaxis.grid(False)

    fig_ev.suptitle(
        "Evaluasi Model Prophet per Bahan Baku — Data Test (25%)",
        fontsize=12, fontweight="bold", y=1.01,
    )
    fig_ev.tight_layout(pad=2)
    chart_evaluation = _fig_to_b64(fig_ev)

    # ─────────────────────────────────────────────────────────────────────
    # GRAFIK 4 — all_items: grid prediksi vs aktual semua bahan baku
    # ─────────────────────────────────────────────────────────────────────
    cols_g = 2
    rows_g = math.ceil(n / cols_g)
    fig_all, axes_all = plt.subplots(rows_g, cols_g, figsize=(16, rows_g * 3.6 + 1), squeeze=False)

    for idx, it in enumerate(all_items_store):
        r_i, c_i = divmod(idx, cols_g)
        ax_i     = axes_all[r_i][c_i]
        train_ds, train_y = it["train"]

        ax_i.plot(train_ds, train_y, color="#93c5fd", linewidth=0.9, alpha=0.75, label="Train")
        ax_i.plot(it["test_ds"], it["test_y"],
                  color="#059669", linewidth=1.2, marker="o", markersize=2.5, label="Test Aktual")
        ax_i.plot(it["test_ds"], it["pred_y"],
                  color="#f97316", linewidth=1.2, linestyle="--", label="Test Pred")
        ax_i.set_title(it["bahan"], fontsize=9.5)
        ax_i.set_ylabel(it["satuan"] or "unit", fontsize=8)
        ax_i.xaxis.set_major_formatter(mdates.DateFormatter("%b %y"))
        ax_i.xaxis.set_major_locator(mdates.AutoDateLocator())
        plt.setp(ax_i.get_xticklabels(), rotation=25, ha="right", fontsize=7.5)
        ax_i.legend(fontsize=7, ncol=3)

    for empty_idx in range(n, rows_g * cols_g):
        r_e, c_e = divmod(empty_idx, cols_g)
        axes_all[r_e][c_e].set_visible(False)

    fig_all.suptitle("Prediksi vs Aktual — Semua Bahan Baku", fontsize=12, fontweight="bold")
    fig_all.tight_layout(pad=2)
    chart_all_items = _fig_to_b64(fig_all)

    # ─────────────────────────────────────────────────────────────────────
    # Forecast range dates
    # ─────────────────────────────────────────────────────────────────────
    if predictions_out and predictions_out[0]["forecast"]:
        fc_from = predictions_out[0]["forecast"][0]["tanggal"]
        fc_to   = predictions_out[0]["forecast"][-1]["tanggal"]
    else:
        fc_from = fc_to = ""

    return {
        "status":             "success",
        "total_ingredients":  n,
        "forecast_days":      2,
        "date_range":         {"from": str(min_date.date()), "to": str(max_date.date())},
        "forecast_range":     {"from": fc_from, "to": fc_to},
        "predictions":        predictions_out,
        "summary_table":      summary_rows,
        "preprocessing_logs": logs,
        "charts": {
            "forecast_all":       chart_forecast_all,
            "feature_importance": chart_feature_importance,
            "evaluation":         chart_evaluation,
            "all_items":          chart_all_items,
            "per_ingredient":     per_ingredient_charts,
        },
    }