"""
Pipeline Prediksi Time Series per Menu — Prophet
W9 Cafe POS | Capstone STIE Totalwin

Mengikuti notebook: 1 Model Menu Prophet preprocessing_prediction.ipynb
- Split 75:25 (cell 28)
- Konfigurasi Prophet tetap: yearly=False, weekly=True, additive,
  changepoint_prior_scale=0.03, seasonality_prior_scale=7 (cell 29)
- Model di-fit hanya pada data train (75%)
- Metrik: MAE, RMSE, MAPE, SMAPE (cell 29)
- Feature Importance: Weekday vs Weekend (cell 30)
- Evaluasi 2×2 bar chart (cell 31)
- Visualisasi per item dengan data training (cell 32)
- Prediksi 2 hari ke depan + nama hari Indonesia (cell 33)
"""

import io, os, base64, warnings, math
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
    'Monday':    'Senin',
    'Tuesday':   'Selasa',
    'Wednesday': 'Rabu',
    'Thursday':  'Kamis',
    'Friday':    'Jumat',
    'Saturday':  'Sabtu',
    'Sunday':    'Minggu',
}


# ─────────────────────────────────────────────────────────────────────────────
# Helper
# ─────────────────────────────────────────────────────────────────────────────

def _fig_to_base64(fig) -> str:
    buf = io.BytesIO()
    fig.savefig(buf, format="png", bbox_inches="tight", dpi=90)
    buf.seek(0)
    b64 = base64.b64encode(buf.read()).decode()
    plt.close(fig)
    return b64


def smape(y_true, y_pred):
    """Symmetric Mean Absolute Percentage Error (cell 27)"""
    y_true, y_pred = np.array(y_true), np.array(y_pred)
    denominator = (np.abs(y_true) + np.abs(y_pred)) / 2
    mask = denominator != 0
    if mask.sum() == 0:
        return 0.0
    return float(np.mean(np.abs(y_true[mask] - y_pred[mask]) / denominator[mask]) * 100)


def mape(y_true, y_pred):
    """Mean Absolute Percentage Error (cell 27)"""
    y_true, y_pred = np.array(y_true), np.array(y_pred)
    mask = y_true != 0
    if mask.sum() == 0:
        return 0.0
    return float(np.mean(np.abs((y_true[mask] - y_pred[mask]) / y_true[mask])) * 100)


def get_day_type(date) -> str:
    """Weekday / Weekend (cell 27)"""
    return 'Weekend' if pd.Timestamp(date).dayofweek >= 5 else 'Weekday'


# ─────────────────────────────────────────────────────────────────────────────
# TAHAP 1 — PREPROCESSING  (cell 3–21)
# ─────────────────────────────────────────────────────────────────────────────

def preprocess(df: pd.DataFrame):
    logs = []

    # Cell 3
    df["Tanggal"] = pd.to_datetime(df["Tanggal"])

    # Cell 7–8 — urutkan & agregasi harian
    df_sorted = df.sort_values("Tanggal", ascending=True)
    df_total  = df_sorted.groupby(
        ["Tanggal", "Nama Item"], as_index=False
    )["Jumlah"].sum()

    # Cell 9 — Day_Type
    df_total["Day_Type"] = df_total["Tanggal"].dt.dayofweek.apply(
        lambda x: "Weekend" if x >= 5 else "Weekday"
    )
    logs.append({
        "tahap":  "Agregasi Harian & Day_Type",
        "detail": (
            f"Diagregasi per Tanggal×Menu. "
            f"Baris: {len(df_total)}, menu unik: {df_total['Nama Item'].nunique()}."
        ),
    })

    # Cell 12–13 — lengkapi tanggal yang hilang per item (isi Jumlah=0)
    df_total["Tanggal"] = pd.to_datetime(df_total["Tanggal"])
    df_full_all = []
    for item in df_total["Nama Item"].unique():
        df_item   = df_total[df_total["Nama Item"] == item].copy()
        all_dates = pd.date_range(
            df_item["Tanggal"].min(), df_item["Tanggal"].max(), freq="D"
        )
        df_full              = pd.DataFrame({"Tanggal": all_dates})
        df_full              = df_full.merge(df_item, on="Tanggal", how="left")
        df_full["Jumlah"]    = df_full["Jumlah"].fillna(0)
        df_full["Nama Item"] = item
        df_full              = df_full[["Tanggal", "Nama Item", "Jumlah"]]
        df_full_all.append(df_full)

    df_final = pd.concat(df_full_all, ignore_index=True)
    df_final = df_final.sort_values(["Nama Item", "Tanggal"]).reset_index(drop=True)
    logs.append({
        "tahap":  "Lengkapi Tanggal Kosong",
        "detail": f"Tanggal hilang diisi Jumlah=0. Total baris: {len(df_final)}.",
    })

    # Cell 15 — tambah ulang Day_Type
    df_final["Day_Type"] = df_final["Tanggal"].dt.dayofweek.apply(
        lambda x: "Weekend" if x >= 5 else "Weekday"
    )

    # Cell 18 — outlier IQR Capping per item
    df_result_list = []
    outlier_total  = 0
    for item in df_final["Nama Item"].unique():
        df_item = df_final[df_final["Nama Item"] == item].copy()
        Q1      = df_item["Jumlah"].quantile(0.25)
        Q3      = df_item["Jumlah"].quantile(0.75)
        IQR     = Q3 - Q1
        lower   = math.floor(Q1 - 1.5 * IQR)
        upper   = math.ceil(Q3 + 1.5 * IQR)
        outlier_total += int(
            ((df_item["Jumlah"] < lower) | (df_item["Jumlah"] > upper)).sum()
        )
        df_item["Jumlah"] = np.where(
            df_item["Jumlah"] > upper, upper,
            np.where(df_item["Jumlah"] < lower, lower, df_item["Jumlah"]),
        )
        df_result_list.append(df_item)

    df_capped = pd.concat(df_result_list, ignore_index=True)
    logs.append({
        "tahap":  "Outlier IQR Capping",
        "detail": f"Total outlier di-cap: {outlier_total} baris.",
    })

    # Cell 21 — bulatkan ke int
    df_capped["Jumlah"] = df_capped["Jumlah"].round().astype(int)

    logs.append({
        "tahap":  "Data Siap",
        "detail": (
            f"Preprocessing selesai. "
            f"{df_capped['Nama Item'].nunique()} menu, "
            f"periode {df_capped['Tanggal'].min().date()} "
            f"s/d {df_capped['Tanggal'].max().date()}."
        ),
    })
    return df_capped, logs


# ─────────────────────────────────────────────────────────────────────────────
# TAHAP 2 — SPLIT 75:25 PER MENU  (cell 28)
# ─────────────────────────────────────────────────────────────────────────────

def prepare_menu_data(df_capped: pd.DataFrame, menu_name: str):
    """Filter per menu, rename kolom (ds, y), encode is_weekend, split 75:25."""
    df_menu = df_capped[df_capped["Nama Item"] == menu_name].copy()
    df_menu = df_menu.sort_values("Tanggal").reset_index(drop=True)
    df_menu = df_menu.rename(columns={"Tanggal": "ds", "Jumlah": "y"})
    df_menu["ds"]         = pd.to_datetime(df_menu["ds"])
    df_menu["is_weekend"] = (
        df_menu["Day_Type"].str.strip().str.lower() == "weekend"
    ).astype(int)

    split_idx = int(len(df_menu) * 0.75)
    df_train  = df_menu.iloc[:split_idx].copy()
    df_test   = df_menu.iloc[split_idx:].copy()
    return df_menu, df_train, df_test


# ─────────────────────────────────────────────────────────────────────────────
# TAHAP 3 — INISIALISASI & FIT MODEL PROPHET  (cell 29 — konfigurasi tetap)
# ─────────────────────────────────────────────────────────────────────────────

def build_prophet_model(df_train: pd.DataFrame) -> Prophet:
    """
    Konfigurasi Prophet tetap sesuai notebook cell 29.
    Model di-fit hanya pada data train (75%).
    uncertainty_samples=100 (lebih cepat dari default 1000, tetap ada CI).
    """
    model = Prophet(
        yearly_seasonality      = False,
        weekly_seasonality      = True,
        daily_seasonality       = False,
        seasonality_mode        = 'additive',
        interval_width          = 0.95,
        changepoint_prior_scale = 0.07,
        seasonality_prior_scale = 8,
        uncertainty_samples     = 100,
    )
    model.add_regressor('is_weekend')
    model.fit(df_train[["ds", "y", "is_weekend"]])
    return model


# ─────────────────────────────────────────────────────────────────────────────
# TAHAP 4 — EVALUASI MODEL  (cell 29)
# ─────────────────────────────────────────────────────────────────────────────

def evaluate_model(model: Prophet, df_test: pd.DataFrame):
    """Prediksi pada test set, hitung MAE / RMSE / MAPE / SMAPE."""
    future_test   = df_test[["ds", "is_weekend"]].copy()
    forecast_test = model.predict(future_test)

    result = df_test[["ds", "y", "Day_Type"]].merge(
        forecast_test[["ds", "yhat", "yhat_lower", "yhat_upper"]], on="ds"
    )
    result["yhat"]       = result["yhat"].clip(lower=0).round()
    result["yhat_lower"] = result["yhat_lower"].clip(lower=0).round()
    result["yhat_upper"] = result["yhat_upper"].clip(lower=0).round()

    y_true = result["y"].values
    y_pred = result["yhat"].values

    mae_val   = float(mean_absolute_error(y_true, y_pred))
    rmse_val  = float(np.sqrt(mean_squared_error(y_true, y_pred)))
    mape_val  = mape(y_true, y_pred)
    smape_val = smape(y_true, y_pred)

    return result, mae_val, rmse_val, mape_val, smape_val


# ─────────────────────────────────────────────────────────────────────────────
# VISUALISASI 1 — FEATURE IMPORTANCE: Weekday vs Weekend  (cell 30)
# ─────────────────────────────────────────────────────────────────────────────

def plot_feature_importance(item_data: dict, items: list) -> str:
    fi_list = []
    for item in items:
        df_all       = item_data[item]["full"]
        weekday_data = df_all[df_all["is_weekend"] == 0]["y"]
        weekend_data = df_all[df_all["is_weekend"] == 1]["y"]
        avg_weekday  = float(weekday_data.mean()) if len(weekday_data) > 0 else 0.0
        avg_weekend  = float(weekend_data.mean()) if len(weekend_data) > 0 else 0.0
        fi_list.append({"item": item, "Weekday": avg_weekday, "Weekend": avg_weekend})

    fig, ax = plt.subplots(figsize=(12, 5))
    x     = np.arange(len(items))
    width = 0.35

    bars1 = ax.bar(x - width / 2, [f["Weekday"] for f in fi_list],
                   width, label="Weekday", color="steelblue", alpha=0.85)
    bars2 = ax.bar(x + width / 2, [f["Weekend"] for f in fi_list],
                   width, label="Weekend", color="coral", alpha=0.85)

    for bar in bars1:
        ax.text(bar.get_x() + bar.get_width() / 2., bar.get_height() + 0.05,
                f"{bar.get_height():.1f}", ha="center", va="bottom", fontsize=8)
    for bar in bars2:
        ax.text(bar.get_x() + bar.get_width() / 2., bar.get_height() + 0.05,
                f"{bar.get_height():.1f}", ha="center", va="bottom", fontsize=8)

    ax.set_xlabel("Item", fontsize=11)
    ax.set_ylabel("Rata-rata Penjualan", fontsize=11)
    ax.set_title(
        "Analisis Rata-rata Penjualan per Menu: Weekday vs Weekend",
        fontsize=13, fontweight="bold",
    )
    ax.set_xticks(x)
    ax.set_xticklabels(items, rotation=30, ha="right")
    ax.legend()
    ax.grid(axis="y", linestyle="--", alpha=0.5)
    fig.tight_layout()
    return _fig_to_base64(fig)


# ─────────────────────────────────────────────────────────────────────────────
# VISUALISASI 2 — EVALUASI 2×2 BAR CHART  (cell 31)
# ─────────────────────────────────────────────────────────────────────────────

def plot_evaluation(eval_results: dict, items: list) -> str:
    rows = [
        {
            "Item":      m,
            "MAE":       eval_results[m]["MAE"],
            "RMSE":      eval_results[m]["RMSE"],
            "MAPE (%)":  eval_results[m]["MAPE"],
            "SMAPE (%)": eval_results[m]["SMAPE"],
        }
        for m in items
    ]
    df_eval = pd.DataFrame(rows)

    fig, axes = plt.subplots(2, 2, figsize=(14, 8))
    metrics   = ["MAE", "RMSE", "MAPE (%)", "SMAPE (%)"]
    colors    = ["steelblue", "darkorange", "seagreen", "mediumpurple"]

    for ax, metric, color in zip(axes.flatten(), metrics, colors):
        bars = ax.bar(df_eval["Item"], df_eval[metric],
                      color=color, alpha=0.85, edgecolor="white")
        ax.set_title(metric, fontsize=12, fontweight="bold")
        ax.set_xlabel("Item")
        ax.set_ylabel(metric)
        ax.tick_params(axis="x", rotation=30)
        ax.grid(axis="y", linestyle="--", alpha=0.5)
        for bar in bars:
            ax.text(
                bar.get_x() + bar.get_width() / 2., bar.get_height() * 0.5,
                f"{bar.get_height():.1f}", ha="center", va="bottom",
                fontsize=8, color="white", fontweight="bold",
            )

    fig.suptitle(
        "Evaluasi Model Prophet per Item\n(pada data TEST 20%)",
        fontsize=14, fontweight="bold", y=1.01,
    )
    fig.tight_layout()
    return _fig_to_base64(fig)


# ─────────────────────────────────────────────────────────────────────────────
# VISUALISASI 3 — PREDIKSI vs AKTUAL PER ITEM  (cell 32)
# ─────────────────────────────────────────────────────────────────────────────

def _build_per_item_ax(ax, item: str, train_df, result_df):
    """Gambar satu subplot untuk satu item."""
    # Training (gray)
    ax.plot(train_df["ds"], train_df["y"],
            color="gray", linewidth=1, alpha=0.5, label="Data Training")
    # Aktual test (steelblue)
    ax.plot(result_df["ds"], result_df["y"],
            color="steelblue", linewidth=2, marker="o", markersize=4, label="Aktual (Test)")
    # Prediksi (tomato)
    ax.plot(result_df["ds"], result_df["yhat"],
            color="tomato", linewidth=2, linestyle="--", marker="s", markersize=4, label="Prediksi")
    # CI band (tomato)
    ax.fill_between(result_df["ds"], result_df["yhat_lower"], result_df["yhat_upper"],
                    alpha=0.15, color="tomato", label="Interval Kepercayaan 95%")
    # Weekend shading (gold)
    for _, row in result_df.iterrows():
        if row["Day_Type"] == "Weekend":
            ax.axvspan(
                row["ds"] - pd.Timedelta(hours=12),
                row["ds"] + pd.Timedelta(hours=12),
                alpha=0.08, color="gold",
            )
    ax.set_title(f"Prediksi vs Aktual — {item}", fontsize=13, fontweight="bold")
    ax.set_xlabel("Tanggal")
    ax.set_ylabel("Jumlah Penjualan")
    ax.legend(loc="upper left", fontsize=9)
    ax.grid(True, linestyle="--", alpha=0.4)
    ax.xaxis.set_major_formatter(mdates.DateFormatter("%d-%b"))
    plt.setp(ax.xaxis.get_majorticklabels(), rotation=30)
    ax.text(0.99, 0.97, "Kuning = Weekend", transform=ax.transAxes,
            fontsize=8, va="top", ha="right", alpha=0.7)


def plot_per_item(items: list, item_data: dict, forecasts: dict):
    """
    Kembalikan:
      - chart_all_items: semua item dalam 1 gambar (combined)
      - charts_per_menu : list {'nama', 'chart'} per item
    """
    n = len(items)
    fig_all, axes = plt.subplots(n, 1, figsize=(14, 5 * n))
    if n == 1:
        axes = [axes]

    for ax, item in zip(axes, items):
        _build_per_item_ax(ax, item, item_data[item]["train"], forecasts[item])

    fig_all.suptitle(
        "Visualisasi Prediksi vs Aktual per Item\n(Data Test 20%)",
        fontsize=15, fontweight="bold", y=1.01,
    )
    fig_all.tight_layout()
    chart_all_items = _fig_to_base64(fig_all)

    # Individual per-menu charts
    charts_per_menu = []
    for item in items:
        fig_single, ax_single = plt.subplots(figsize=(14, 5))
        _build_per_item_ax(ax_single, item, item_data[item]["train"], forecasts[item])
        fig_single.tight_layout()
        charts_per_menu.append({"nama": item, "chart": _fig_to_base64(fig_single)})

    return chart_all_items, charts_per_menu


# ─────────────────────────────────────────────────────────────────────────────
# MAIN PIPELINE
# ─────────────────────────────────────────────────────────────────────────────

def run_prediction_pipeline(df: pd.DataFrame) -> dict:
    """
    Input  : DataFrame dari fetch_order_data() (api.py)
    Output : dict JSON untuk endpoint /prediction
    """

    # ── Tahap 1: Preprocessing ────────────────────────────────────────────
    df_capped, logs = preprocess(df)

    min_date = df_capped["Tanggal"].min()
    max_date = df_capped["Tanggal"].max()
    items    = list(df_capped["Nama Item"].unique())

    # ── Tahap 2: Split 75:25 per menu (cell 28) ───────────────────────────
    item_data = {}
    skipped_items = []
    for item in items:
        df_full, df_train, df_test = prepare_menu_data(df_capped, item)
        if len(df_train) < 2 or len(df_test) < 1:
            skipped_items.append(item)
            continue
        item_data[item] = {"full": df_full, "train": df_train, "test": df_test}

    items = list(item_data.keys())

    if not items:
        raise ValueError(
            "Semua menu tidak memiliki cukup data untuk diprediksi. "
            "Setiap menu butuh minimal 3 hari data transaksi."
        )

    logs.append({
        "tahap":  "Persiapan Data Per Menu",
        "detail": (
            f"Data dibagi train (75%) dan test (25%) untuk {len(items)} menu."
            + (f" Dilewati ({len(skipped_items)} menu data kurang): {', '.join(skipped_items)}." if skipped_items else "")
        ),
    })

    # ── Tahap 3 & 4: Training + Evaluasi (cell 29) ───────────────────────
    models       = {}
    eval_results = {}
    forecasts    = {}

    for item in items:
        model = build_prophet_model(item_data[item]["train"])
        models[item] = model

        result, mae_v, rmse_v, mape_v, smape_v = evaluate_model(
            model, item_data[item]["test"]
        )
        eval_results[item] = {
            "MAE":   round(mae_v,   2),
            "RMSE":  round(rmse_v,  2),
            "MAPE":  round(mape_v,  2),
            "SMAPE": round(smape_v, 2),
        }
        forecasts[item] = result

    logs.append({
        "tahap":  "Training Model Prophet & Evaluasi",
        "detail": (
            f"1 model Prophet per menu, difit pada data train (75%), "
            f"dievaluasi pada test (25%). "
            + " | ".join(
                f"{m}: MAPE {eval_results[m]['MAPE']:.1f}%  SMAPE {eval_results[m]['SMAPE']:.1f}%"
                for m in items
            )
        ),
    })

    # ── Visualisasi ────────────────────────────────────────────────────────
    chart_feat_imp          = plot_feature_importance(item_data, items)
    chart_eval              = plot_evaluation(eval_results, items)
    chart_all_items, charts_per_menu = plot_per_item(items, item_data, forecasts)

    # ── Tahap 5: Prediksi 2 hari ke depan (cell 33) ───────────────────────
    future_dates = [max_date + pd.Timedelta(days=i) for i in range(1, 3)]
    future_df    = pd.DataFrame({
        "ds":         future_dates,
        "is_weekend": [1 if d.dayofweek >= 5 else 0 for d in future_dates],
    })

    predictions_out = []
    summary_table   = []

    for item in items:
        forecast_future = models[item].predict(future_df)

        forecast_days_list = []
        for _, row in forecast_future.iterrows():
            day_name  = row["ds"].strftime("%A")
            day_indo  = HARI_ID.get(day_name, day_name)
            day_type  = "Weekend" if row["ds"].dayofweek >= 5 else "Weekday"
            pred      = max(0, round(float(row["yhat"])))
            lower     = max(0, round(float(row["yhat_lower"])))
            upper     = max(0, round(float(row["yhat_upper"])))

            forecast_days_list.append({
                "tanggal":     str(row["ds"].date()),
                "hari":        day_indo,
                "day_type":    day_type,
                "prediksi":    pred,
                "batas_bawah": lower,
                "batas_atas":  upper,
            })

        total_forecast = sum(f["prediksi"] for f in forecast_days_list)
        ev = eval_results[item]

        predictions_out.append({
            "nama_menu":      item,
            "model":          "Prophet",
            "mae":            ev["MAE"],
            "rmse":           ev["RMSE"],
            "mape":           ev["MAPE"],
            "smape":          ev["SMAPE"],
            "total_forecast": float(total_forecast),
            "forecast":       forecast_days_list,
        })
        summary_table.append({
            "nama_menu":      item,
            "total_forecast": float(total_forecast),
            "avg_per_day":    round(total_forecast / 2, 2),
            "mae":            ev["MAE"],
            "rmse":           ev["RMSE"],
            "mape":           ev["MAPE"],
            "smape":          ev["SMAPE"],
            "model":          "Prophet",
        })

    summary_table.sort(key=lambda x: x["total_forecast"], reverse=True)

    d1_name = HARI_ID.get(future_dates[0].strftime("%A"), "")
    d2_name = HARI_ID.get(future_dates[1].strftime("%A"), "")
    logs.append({
        "tahap":  "Prediksi 2 Hari ke Depan",
        "detail": (
            f"Forecast: {future_dates[0].date()} ({d1_name}) "
            f"dan {future_dates[1].date()} ({d2_name})."
        ),
    })

    # ── Chart ringkasan total forecast semua menu ──────────────────────────
    names  = [s["nama_menu"]      for s in summary_table]
    totals = [s["total_forecast"] for s in summary_table]

    fig_sum, ax_sum = plt.subplots(figsize=(max(7, len(names) * 1.6), 4.5))
    bars = ax_sum.bar(names, totals, color="#6366f1", width=0.55, edgecolor="white")
    for bar, val in zip(bars, totals):
        ax_sum.text(
            bar.get_x() + bar.get_width() / 2, bar.get_height() + 0.06,
            str(int(val)), ha="center", va="bottom",
            fontsize=11, fontweight="bold", color="#374151",
        )
    ax_sum.set_title(
        f"Total Prediksi Penjualan per Menu\n"
        f"{future_dates[0].date()} ({d1_name})  s/d  {future_dates[1].date()} ({d2_name})",
        fontsize=11, fontweight="bold", pad=12,
    )
    ax_sum.set_xlabel("Nama Menu", labelpad=8)
    ax_sum.set_ylabel("Total Prediksi (unit)", labelpad=8)
    ax_sum.yaxis.grid(True)
    ax_sum.xaxis.grid(False)
    fig_sum.tight_layout(pad=2)
    chart_summary = _fig_to_base64(fig_sum)

    # ── Output akhir ───────────────────────────────────────────────────────
    return {
        "status":             "success",
        "total_menu":         int(len(items)),
        "forecast_days":      2,
        "date_range":         {
            "from": str(min_date.date()),
            "to":   str(max_date.date()),
        },
        "forecast_range":     {
            "from": str(future_dates[0].date()),
            "to":   str(future_dates[1].date()),
        },
        "preprocessing_logs": logs,
        "predictions":        predictions_out,
        "summary_table":      summary_table,
        "charts": {
            "forecast_all":       chart_summary,
            "feature_importance": chart_feat_imp,
            "evaluation":         chart_eval,
            "all_items":          chart_all_items,
            "per_menu":           charts_per_menu,
        },
    }
