"""
K-Means Clustering — Bahan Baku
W9 Cafe POS | Capstone STIE Totalwin

Pipeline mengikuti notebook: Revisi_Bahan_Baku_FIX_KMEANS_Preprocessing_Clustering_(Stok_Bahan_Baku).ipynb
"""

import io, base64, math, warnings
import numpy as np
import pandas as pd
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
import matplotlib.patches as mpatches
from matplotlib.patches import Patch

from sklearn.preprocessing import MinMaxScaler
from sklearn.cluster import KMeans
from sklearn.metrics import silhouette_score

warnings.filterwarnings("ignore")


def _to_b64(fig) -> str:
    buf = io.BytesIO()
    fig.savefig(buf, format="png", bbox_inches="tight", dpi=120)
    buf.seek(0)
    enc = base64.b64encode(buf.read()).decode()
    plt.close(fig)
    return enc


def run_bahan_baku_pipeline(df: pd.DataFrame) -> dict:
    """
    Pipeline K-Means clustering bahan baku sesuai notebook cells 3–36.
    Input : DataFrame dengan kolom: Tanggal, Bahan_Baku, Unit, Jumlah_Digunakan
    Output: dict JSON-serializable
    """
    logs = []

    # ── Cell 3: pilih kolom yang dibutuhkan ───────────────────────────
    df_select = df[["Tanggal", "Bahan_Baku", "Jumlah_Digunakan"]].copy()

    # ── Cell 4: parse Tanggal ──────────────────────────────────────────
    df_select["Tanggal"] = pd.to_datetime(df_select["Tanggal"])

    # ── Cell 8: urutkan berdasarkan tanggal ───────────────────────────
    df_sorted = df_select.sort_values("Tanggal", ascending=True)

    date_from = str(df_sorted["Tanggal"].min().date())
    date_to   = str(df_sorted["Tanggal"].max().date())

    # ── Cell 9: agregasi harian per bahan baku ─────────────────────────
    df_total = df_sorted.groupby(["Tanggal", "Bahan_Baku"], as_index=False)["Jumlah_Digunakan"].sum()

    # ── Cell 10: tambahkan Day_Type ────────────────────────────────────
    df_total["Day_Type"] = df_total["Tanggal"].dt.dayofweek.apply(
        lambda x: "Weekend" if x >= 5 else "Weekday"
    )
    logs.append({
        "tahap":  "Agregasi Harian",
        "detail": (
            f"Diagregasi per Tanggal × Bahan Baku. "
            f"Baris: {len(df_total)}, bahan baku unik: {df_total['Bahan_Baku'].nunique()}."
        ),
    })

    # ── Cell 13: lengkapi tanggal yang hilang (0) ──────────────────────
    df_total["Tanggal"] = pd.to_datetime(df_total["Tanggal"])
    df_full_all = []
    for item in df_total["Bahan_Baku"].unique():
        df_item = df_total[df_total["Bahan_Baku"] == item].copy()
        all_dates = pd.date_range(df_item["Tanggal"].min(), df_item["Tanggal"].max(), freq="D")
        df_full = pd.DataFrame({"Tanggal": all_dates})
        df_full = df_full.merge(df_item, on="Tanggal", how="left")
        df_full["Jumlah_Digunakan"] = df_full["Jumlah_Digunakan"].fillna(0)
        df_full["Bahan_Baku"] = item
        df_full = df_full[["Tanggal", "Bahan_Baku", "Jumlah_Digunakan"]]
        df_full_all.append(df_full)

    df_final = pd.concat(df_full_all, ignore_index=True)
    df_final = df_final.sort_values(["Bahan_Baku", "Tanggal"]).reset_index(drop=True)

    # ── Cell 15: Day_Type ulang setelah lengkapi ───────────────────────
    df_final["Day_Type"] = df_final["Tanggal"].dt.dayofweek.apply(
        lambda x: "Weekend" if x >= 5 else "Weekday"
    )
    logs.append({
        "tahap":  "Lengkapi Tanggal Kosong",
        "detail": f"Tanggal hilang diisi Jumlah_Digunakan=0. Total baris: {len(df_final)}.",
    })

    # ── Cell 18: IQR Capping per bahan baku ───────────────────────────
    df_result_list = []
    outlier_total = 0
    for item in df_final["Bahan_Baku"].unique():
        df_item = df_final[df_final["Bahan_Baku"] == item].copy()
        Q1 = df_item["Jumlah_Digunakan"].quantile(0.25)
        Q3 = df_item["Jumlah_Digunakan"].quantile(0.75)
        IQR = Q3 - Q1
        lower = math.floor(Q1 - 1.5 * IQR)
        upper = math.ceil(Q3 + 1.5 * IQR)
        n_out = int(((df_item["Jumlah_Digunakan"] < lower) | (df_item["Jumlah_Digunakan"] > upper)).sum())
        outlier_total += n_out
        df_item["Jumlah_Digunakan"] = np.where(
            df_item["Jumlah_Digunakan"] > upper, upper,
            np.where(df_item["Jumlah_Digunakan"] < lower, lower, df_item["Jumlah_Digunakan"])
        )
        df_result_list.append(df_item)

    df_capped = pd.concat(df_result_list, ignore_index=True)
    logs.append({
        "tahap":  "Outlier IQR Capping (per Bahan Baku)",
        "detail": f"Total nilai outlier di-cap: {outlier_total} baris.",
    })

    # ── Cell 20: agregasi total per bahan baku ─────────────────────────
    df_total2 = df_capped.groupby("Bahan_Baku", as_index=False)["Jumlah_Digunakan"].sum()

    # ── Cells 22-24: IQR Capping pada total agregat ────────────────────
    Q1 = df_total2["Jumlah_Digunakan"].quantile(0.25)
    Q3 = df_total2["Jumlah_Digunakan"].quantile(0.75)
    IQR = Q3 - Q1
    lower_bound = Q1 - 1.5 * IQR
    upper_bound = Q3 + 1.5 * IQR

    df_capped2 = df_total2.copy()
    df_capped2["Jumlah_Digunakan"] = df_capped2["Jumlah_Digunakan"].clip(
        lower=lower_bound, upper=upper_bound
    )

    n_out_total = int(
        ((df_total2["Jumlah_Digunakan"] < lower_bound) | (df_total2["Jumlah_Digunakan"] > upper_bound)).sum()
    )
    logs.append({
        "tahap":  "Outlier IQR Capping (Total Agregat)",
        "detail": f"Outlier total agregat di-cap: {n_out_total} bahan baku.",
    })

    # ── Cell 25-26: Feature Scaling (MinMaxScaler) ─────────────────────
    x_train = df_capped2["Jumlah_Digunakan"].values.reshape(-1, 1)
    scaler  = MinMaxScaler()
    x_train = scaler.fit_transform(x_train)
    logs.append({
        "tahap":  "Feature Scaling",
        "detail": f"MinMaxScaler diterapkan pada {len(df_capped2)} bahan baku.",
    })

    # ── Cell 28: Silhouette Score — tentukan best_k ────────────────────
    n_items = len(x_train)
    if n_items < 2:
        return {
            "status":  "error",
            "message": (
                f"Data tidak cukup untuk clustering. "
                f"Hanya ditemukan {n_items} bahan baku unik. "
                "Minimal diperlukan 2 bahan baku."
            ),
        }

    silhouette_scores = []
    k_range = range(2, min(10, n_items))
    for k in k_range:
        km     = KMeans(n_clusters=k, random_state=42, n_init=10)
        labels = km.fit_predict(x_train)
        silhouette_scores.append(silhouette_score(x_train, labels))

    if not silhouette_scores:
        best_k   = 2
        km       = KMeans(n_clusters=best_k, random_state=42, n_init=10)
        labels   = km.fit_predict(x_train)
        best_sil = float(silhouette_score(x_train, labels))
        silhouette_scores = [best_sil]
        k_range  = range(2, 3)
    else:
        best_k   = list(k_range)[int(np.argmax(silhouette_scores))]
        best_sil = float(max(silhouette_scores))

    logs.append({
        "tahap":  "Penentuan K Optimal (Silhouette)",
        "detail": f"K terbaik: {best_k} (Silhouette Score: {best_sil:.4f}). Range K: 2–{max(k_range)}.",
    })

    # ── Elbow — hitung inertia ─────────────────────────────────────────
    inertias  = []
    k_range_e = range(2, min(10, n_items))
    for k in k_range_e:
        km = KMeans(n_clusters=k, random_state=42, n_init=10)
        km.fit(x_train)
        inertias.append(km.inertia_)

    # ── K-Means fit (cell 29) ──────────────────────────────────────────
    kmean = KMeans(n_clusters=best_k, random_state=42, n_init=10)
    df_capped2["Klaster"] = kmean.fit_predict(x_train)

    logs.append({
        "tahap":  "K-Means Clustering",
        "detail": f"K-Means dijalankan: K={best_k}, random_state=42, n_init=10.",
    })

    # ── Cell 32: urutkan berdasarkan Jumlah_Digunakan ──────────────────
    df_bbaku = df_capped2.sort_values(by="Jumlah_Digunakan")

    # ── Cell 33: laporan klasterisasi (Klaster asc, Jumlah desc) ───────
    df_laporan = (
        df_capped2
        .sort_values(by=["Klaster", "Jumlah_Digunakan"], ascending=[True, False])
        .reset_index(drop=True)
    )
    df_laporan.index = df_laporan.index + 1

    # ── Cell 34: rata-rata per klaster (sort desc) ─────────────────────
    rata_rata_cluster = (
        df_capped2
        .groupby("Klaster")["Jumlah_Digunakan"]
        .mean()
        .reset_index()
    )
    rata_rata_cluster.columns = ["Klaster", "Rata-rata Jumlah Penggunaan"]
    rata_rata_cluster = rata_rata_cluster.sort_values(
        by="Rata-rata Jumlah Penggunaan", ascending=False
    ).reset_index(drop=True)

    logs.append({
        "tahap":  "Analisis Rata-rata per Klaster",
        "detail": "Rata-rata jumlah penggunaan dihitung per klaster, diurutkan dari tertinggi ke terendah.",
    })

    # ── Warna per klaster: tab10 colormap (otomatis sesuai jumlah klaster) ──
    cluster_ids  = sorted(df_capped2["Klaster"].unique())
    cmap         = plt.cm.get_cmap("tab10", len(cluster_ids))
    warna_klaster = {int(c): cmap(i) for i, c in enumerate(cluster_ids)}

    # ── Style global matplotlib ────────────────────────────────────────
    plt.rcParams.update({
        "font.family":           "DejaVu Sans",
        "axes.spines.top":       False,
        "axes.spines.right":     False,
        "axes.titlesize":        14,
        "axes.titleweight":      "bold",
        "axes.titlepad":         16,
        "axes.labelsize":        12,
        "axes.labelpad":         8,
        "xtick.labelsize":       10,
        "ytick.labelsize":       10,
        "legend.fontsize":       10,
        "legend.title_fontsize": 11,
        "figure.facecolor":      "white",
        "axes.facecolor":        "#fafafa",
        "axes.grid":             True,
        "grid.color":            "#e5e7eb",
        "grid.linewidth":        0.8,
    })

    # ── VISUALISASI 1: Rata-rata per Klaster (notebook cell 35) ───────
    fig1, ax1 = plt.subplots(figsize=(10, 6))

    colors_rata = [warna_klaster[int(k)] for k in rata_rata_cluster["Klaster"]]
    bars1 = ax1.bar(
        rata_rata_cluster["Klaster"].astype(str),
        rata_rata_cluster["Rata-rata Jumlah Penggunaan"],
        color=colors_rata,
    )
    for bar in bars1:
        height = bar.get_height()
        ax1.text(
            bar.get_x() + bar.get_width() / 2,
            height,
            f"{height:.2f}",
            ha="center", va="bottom",
            fontsize=10, fontweight="bold",
        )

    legend_elements1 = [
        Patch(facecolor=warna_klaster[int(k)], label=f"Klaster {k}")
        for k in cluster_ids
    ]
    ax1.legend(handles=legend_elements1, title="Keterangan Warna", loc="best",
               framealpha=0.9, edgecolor="#e5e7eb", fancybox=False)
    ax1.set_title("Rata-rata Jumlah Penggunaan Bahan Baku per Klaster", pad=18)
    ax1.set_xlabel("Klaster", labelpad=10)
    ax1.set_ylabel("Rata-rata Jumlah Penggunaan", labelpad=10)
    ax1.grid(axis="y", linestyle="--", alpha=0.5)
    ax1.xaxis.grid(False)
    fig1.tight_layout(pad=2)
    chart_rata_klaster = _to_b64(fig1)

    # ── VISUALISASI 2: Jumlah per Bahan Baku colored by Klaster (notebook cell 36) ──
    n_baku = len(df_bbaku)
    fig_w  = max(14, n_baku * 0.85)
    fig2, ax2 = plt.subplots(figsize=(fig_w, 7))

    bar_colors = [warna_klaster[int(k)] for k in df_bbaku["Klaster"]]

    bars2 = ax2.bar(
        df_bbaku["Bahan_Baku"],
        df_bbaku["Jumlah_Digunakan"],
        color=bar_colors,
        edgecolor="white",
        linewidth=0.8,
        width=0.6,
    )

    max_val = df_bbaku["Jumlah_Digunakan"].max() if not df_bbaku.empty else 1
    for bar in bars2:
        h = bar.get_height()
        if h > 0:
            ax2.text(
                bar.get_x() + bar.get_width() / 2,
                h,
                f"{h:.0f}",
                ha="center", va="bottom",
                fontsize=8, color="#374151",
            )

    legend_patches = [
        mpatches.Patch(color=warna_klaster[int(k)], label=f"Klaster {k}")
        for k in sorted(df_bbaku["Klaster"].unique())
    ]
    ax2.legend(
        handles=legend_patches,
        title="Keterangan Klaster",
        loc="upper left",
        bbox_to_anchor=(1.01, 1),
        framealpha=0.9, edgecolor="#e5e7eb", fancybox=False,
    )

    ax2.set_title("Visualisasi Jumlah Penggunaan Bahan Baku Berdasarkan Hasil Klasterisasi", pad=18)
    ax2.set_xlabel("Nama Bahan Baku", labelpad=10)
    ax2.set_ylabel("Jumlah Penggunaan", labelpad=10)
    ax2.set_xticks(range(len(df_bbaku)))
    ax2.set_xticklabels(df_bbaku["Bahan_Baku"], rotation=45, ha="right")
    ax2.set_ylim(0, max_val * 1.15)
    ax2.grid(axis="y", linestyle="--", alpha=0.5)
    ax2.xaxis.grid(False)
    ax2.spines["top"].set_visible(False)
    ax2.spines["right"].set_visible(False)
    fig2.tight_layout(pad=2)
    chart_bar = _to_b64(fig2)

    # ── VISUALISASI 3: Elbow Curve ─────────────────────────────────────
    fig3, ax3 = plt.subplots(figsize=(9, 5))
    ax3.plot(list(k_range_e), inertias,
             marker="o", markersize=8, color="#6366f1",
             linewidth=2.5, markerfacecolor="white", markeredgewidth=2)
    ax3.axvline(x=best_k, color="#ef4444", linestyle="--",
                linewidth=1.8, alpha=0.8, label=f"K optimal = {best_k}")
    best_inertia = inertias[list(k_range_e).index(best_k)]
    ax3.annotate(
        f"  K={best_k}\n  Inertia={best_inertia:.4f}",
        xy=(best_k, best_inertia),
        xytext=(best_k + 0.4, best_inertia + (max(inertias) - min(inertias)) * 0.08),
        fontsize=9, color="#ef4444",
        arrowprops=dict(arrowstyle="->", color="#ef4444", lw=1.2),
    )
    ax3.set_xlabel("Jumlah Klaster (K)")
    ax3.set_ylabel("Inertia (Sum of Squared Error)")
    ax3.set_title("Elbow Method — Penentuan Jumlah Klaster Optimal")
    ax3.set_xticks(list(k_range_e))
    ax3.legend(framealpha=0.9, edgecolor="#e5e7eb", fancybox=False)
    fig3.tight_layout(pad=2)
    chart_elbow = _to_b64(fig3)

    # ── VISUALISASI 4: Silhouette Score per K ─────────────────────────
    fig4, ax4 = plt.subplots(figsize=(9, 5))
    ax4.plot(list(k_range), silhouette_scores,
             marker="s", markersize=8, color="#059669",
             linewidth=2.5, markerfacecolor="white", markeredgewidth=2)
    ax4.axvline(x=best_k, color="#ef4444", linestyle="--",
                linewidth=1.8, alpha=0.8, label=f"K optimal = {best_k}")
    sil_range = (max(silhouette_scores) - min(silhouette_scores)) if len(silhouette_scores) > 1 else 0.1
    ax4.annotate(
        f"  K={best_k}\n  Score={best_sil:.4f}",
        xy=(best_k, best_sil),
        xytext=(best_k + 0.4, best_sil - sil_range * 0.15),
        fontsize=9, color="#ef4444",
        arrowprops=dict(arrowstyle="->", color="#ef4444", lw=1.2),
    )
    ax4.set_xlabel("Jumlah Klaster (K)")
    ax4.set_ylabel("Silhouette Score")
    ax4.set_title("Silhouette Score per K — Kualitas Pengelompokan")
    ax4.set_xticks(list(k_range))
    ax4.legend(framealpha=0.9, edgecolor="#e5e7eb", fancybox=False)
    fig4.tight_layout(pad=2)
    chart_silhouette = _to_b64(fig4)

    # ── Build table_rows (Klaster asc, Jumlah desc) ────────────────────
    table_rows = []
    for _, row in df_laporan.iterrows():
        unit_val = ""
        if "Unit" in df.columns:
            match = df[df["Bahan_Baku"] == row["Bahan_Baku"]]["Unit"]
            if len(match) > 0:
                unit_val = str(match.iloc[0])
        table_rows.append({
            "Nama Bahan Baku":  row["Bahan_Baku"],
            "Satuan":           unit_val,
            "Total Penggunaan": float(row["Jumlah_Digunakan"]),
            "Klaster":          int(row["Klaster"]),
        })

    # ── Build rata_rata_table ──────────────────────────────────────────
    rata_rata_table = [
        {
            "Klaster":                      int(row["Klaster"]),
            "Rata-rata Jumlah Penggunaan":  round(float(row["Rata-rata Jumlah Penggunaan"]), 2),
        }
        for _, row in rata_rata_cluster.iterrows()
    ]

    # ── Clusters output per klaster (untuk summary cards) ─────────────
    clusters_out = []
    for klaster_id in sorted(df_capped2["Klaster"].unique()):
        subset = df_capped2[df_capped2["Klaster"] == klaster_id].sort_values(
            "Jumlah_Digunakan", ascending=False
        )
        clusters_out.append({
            "klaster":     int(klaster_id),
            "count":       len(subset),
            "total_usage": float(subset["Jumlah_Digunakan"].sum()),
            "avg_usage":   round(float(subset["Jumlah_Digunakan"].mean()), 1),
            "ingredients": [
                {
                    "name":   r["Bahan_Baku"],
                    "unit":   (
                        df[df["Bahan_Baku"] == r["Bahan_Baku"]]["Unit"].iloc[0]
                        if "Unit" in df.columns and len(df[df["Bahan_Baku"] == r["Bahan_Baku"]]) > 0
                        else ""
                    ),
                    "jumlah": float(r["Jumlah_Digunakan"]),
                }
                for _, r in subset.iterrows()
            ],
        })

    return {
        "status":             "success",
        "best_k":             best_k,
        "silhouette_score":   round(best_sil, 4),
        "total_ingredients":  int(len(df_capped2)),
        "date_range":         {"from": date_from, "to": date_to},
        "clusters":           clusters_out,
        "table_rows":         table_rows,
        "rata_rata_table":    rata_rata_table,
        "preprocessing_logs": logs,
        "charts": {
            "rata_klaster": chart_rata_klaster,
            "bar":          chart_bar,
            "elbow":        chart_elbow,
            "silhouette":   chart_silhouette,
        },
    }
