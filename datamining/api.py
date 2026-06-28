"""
FastAPI — Data Mining API
W9 Cafe POS | Capstone STIE Totalwin

Endpoints:
  GET  /health            — cek status server
  GET  /preview-data      — preview data dari DB
  POST /clustering        — K-Means Clustering (2-variabel: Jumlah + Keuntungan)
  POST /prediction        — Time Series Prediction per Menu (Prophet)
  POST /association       — Association Rules (Apriori/FP-Growth)
"""

import io, os, base64, warnings, math
from typing import Optional
import numpy as np
import pandas as pd
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.patches import Patch

from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from dotenv import load_dotenv
import psycopg2
from psycopg2.extras import RealDictCursor
from sklearn.preprocessing import MinMaxScaler
from sklearn.cluster import KMeans
from sklearn.metrics import silhouette_score

try:
    from .prediction    import run_prediction_pipeline
    from .association   import run_association_pipeline
    from .bahanbaku     import run_bahan_baku_pipeline
    from .prediksibaku  import run_prediction_pipeline_bahan_baku
except ImportError:
    from prediction    import run_prediction_pipeline
    from association   import run_association_pipeline
    from bahanbaku     import run_bahan_baku_pipeline
    from prediksibaku  import run_prediction_pipeline_bahan_baku

warnings.filterwarnings("ignore")
load_dotenv()

app = FastAPI(title="W9 Cafe — Data Mining API", version="2.0.0")
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)


# ── Koneksi DB ─────────────────────────────────────────────────────────────
def get_connection():
    return psycopg2.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        port=int(os.getenv("DB_PORT", 5432)),
        dbname=os.getenv("DB_NAME", "pos_cafe"),
        user=os.getenv("DB_USER", "postgres"),
        password=os.getenv("DB_PASSWORD", ""),
        cursor_factory=RealDictCursor,
    )


# ── Ambil data pesanan (+ Keuntungan) dengan filter tanggal opsional ───────
def fetch_order_data(date_from: Optional[str] = None,
                     date_to:   Optional[str] = None) -> pd.DataFrame:
    where_clauses = ["o.status = 'selesai'"]
    params: list = []

    if date_from and date_from.strip():
        where_clauses.append("o.created_at::date >= %s")
        params.append(date_from.strip())
    if date_to and date_to.strip():
        where_clauses.append("o.created_at::date <= %s")
        params.append(date_to.strip())

    where_sql = " AND ".join(where_clauses)

    sql = f"""
        SELECT
            o.created_at::date                                        AS "Tanggal",
            o.order_code                                              AS "Order_id",
            m.name                                                    AS "Nama Item",
            oi.quantity                                               AS "Jumlah",
            oi.unit_price::float                                      AS "Harga",
            oi.subtotal::float                                        AS "Subtotal",
            COALESCE(m.harga_modal, 0)::float                        AS "Harga_Modal",
            (oi.subtotal - COALESCE(m.harga_modal, 0) * oi.quantity)::float
                                                                      AS "Keuntungan"
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        JOIN menus  m ON m.id = oi.menu_id
        WHERE {where_sql}
        ORDER BY o.created_at ASC
    """

    conn = get_connection()
    try:
        with conn.cursor() as cur:
            cur.execute(sql, params if params else None)
            rows = cur.fetchall()
    finally:
        conn.close()

    df = pd.DataFrame([dict(r) for r in rows])
    if not df.empty:
        df["Tanggal"] = pd.to_datetime(df["Tanggal"])
    return df


# ── Helper: fig → base64 PNG ───────────────────────────────────────────────
def fig_to_base64(fig) -> str:
    buf = io.BytesIO()
    fig.savefig(buf, format="png", bbox_inches="tight", dpi=120)
    buf.seek(0)
    b64 = base64.b64encode(buf.read()).decode()
    plt.close(fig)
    return b64


# ── PIPELINE (mengikuti 2REV_K_Means_REV_Pesanan.ipynb sel per sel) ────────
def run_pipeline(df: pd.DataFrame) -> dict:
    logs = []

    # ── Parsing tanggal ───────────────────────────────────────────────────
    # Data dari sistem POS sudah bersih: tidak ada missing value atau duplikat
    # karena setiap baris order_item dibuat oleh sistem secara otomatis.
    df["Tanggal"] = pd.to_datetime(df["Tanggal"])
    logs.append({
        "tahap":  "Load Data Riwayat Pesanan",
        "detail": (
            f"Berhasil memuat {len(df)} baris item pesanan dari {df['Nama Item'].nunique()} menu unik. "
            f"Rentang data: {df['Tanggal'].min().date()} s/d {df['Tanggal'].max().date()}."
        ),
    })

    # ── Agregasi harian per menu (cell-7) ─────────────────────────────────
    df_sorted = df.sort_values("Tanggal", ascending=True)
    df_daily = df_sorted.groupby(
        ["Tanggal", "Nama Item"], as_index=False
    )[["Jumlah", "Keuntungan"]].sum()
    logs.append({
        "tahap":  "Agregasi Harian per Menu",
        "detail": (
            f"Diagregasi per Tanggal × Nama Item. "
            f"Baris: {len(df_daily)}, menu unik: {df_daily['Nama Item'].nunique()}."
        ),
    })

    # ── Lengkapi tanggal yang hilang per menu → isi 0 (cell-11) ──────────
    df_daily["Tanggal"] = pd.to_datetime(df_daily["Tanggal"])
    min_date = df_daily["Tanggal"].min()
    max_date = df_daily["Tanggal"].max()

    df_full_all = []
    for item in df_daily["Nama Item"].unique():
        df_item = df_daily[df_daily["Nama Item"] == item].copy()
        all_dates = pd.date_range(
            start=df_item["Tanggal"].min(),
            end=df_item["Tanggal"].max(),
            freq="D",
        )
        df_full = pd.DataFrame({"Tanggal": all_dates})
        df_full = df_full.merge(df_item, on="Tanggal", how="left")
        df_full["Jumlah"]     = df_full["Jumlah"].fillna(0)
        df_full["Keuntungan"] = df_full["Keuntungan"].fillna(0)
        df_full["Nama Item"]  = item
        df_full = df_full[["Tanggal", "Nama Item", "Jumlah", "Keuntungan"]]
        df_full_all.append(df_full)

    df_final = pd.concat(df_full_all, ignore_index=True)
    df_final = df_final.sort_values(["Nama Item", "Tanggal"]).reset_index(drop=True)
    logs.append({
        "tahap":  "Lengkapi Tanggal Kosong",
        "detail": f"Tanggal hilang diisi Jumlah=0, Keuntungan=0. Total baris setelah: {len(df_final)}.",
    })

    # ── Outlier IQR Capping per menu — untuk KEDUA variabel (cell-17) ─────
    df_capped_list = []
    outlier_j = 0
    outlier_k = 0
    for item in df_final["Nama Item"].unique():
        df_item = df_final[df_final["Nama Item"] == item].copy()

        # Jumlah
        Q1_j, Q3_j = df_item["Jumlah"].quantile(0.25), df_item["Jumlah"].quantile(0.75)
        IQR_j = Q3_j - Q1_j
        lo_j = math.floor(Q1_j - 1.5 * IQR_j)
        hi_j = math.ceil(Q3_j + 1.5 * IQR_j)
        outlier_j += int(((df_item["Jumlah"] < lo_j) | (df_item["Jumlah"] > hi_j)).sum())
        df_item["Jumlah"] = df_item["Jumlah"].clip(lower=lo_j, upper=hi_j)

        # Keuntungan
        Q1_k, Q3_k = df_item["Keuntungan"].quantile(0.25), df_item["Keuntungan"].quantile(0.75)
        IQR_k = Q3_k - Q1_k
        lo_k = math.floor(Q1_k - 1.5 * IQR_k)
        hi_k = math.ceil(Q3_k + 1.5 * IQR_k)
        outlier_k += int(((df_item["Keuntungan"] < lo_k) | (df_item["Keuntungan"] > hi_k)).sum())
        df_item["Keuntungan"] = df_item["Keuntungan"].clip(lower=lo_k, upper=hi_k)

        df_capped_list.append(df_item)

    df_capped = pd.concat(df_capped_list, ignore_index=True)
    logs.append({
        "tahap":  "Outlier IQR Capping (Jumlah & Keuntungan)",
        "detail": f"Outlier di-cap: Jumlah={outlier_j} baris, Keuntungan={outlier_k} baris.",
    })

    # ── Agregasi total per menu — 2 variabel (cell-19) ────────────────────
    df_total = df_capped.groupby("Nama Item", as_index=False).agg(
        Total_Jumlah=("Jumlah", "sum"),
        Total_Keuntungan=("Keuntungan", "sum"),
    )
    logs.append({
        "tahap":  "Agregasi Total per Menu",
        "detail": (
            f"Total {len(df_total)} menu. "
            f"Total_Jumlah: min={df_total['Total_Jumlah'].min():.1f}, "
            f"max={df_total['Total_Jumlah'].max():.1f}. "
            f"Total_Keuntungan: min=Rp{df_total['Total_Keuntungan'].min():,.0f}, "
            f"max=Rp{df_total['Total_Keuntungan'].max():,.0f}."
        ),
    })

    # ── Feature Scaling MinMax pada 2 variabel (cell-22) ──────────────────
    x_train = df_total[["Total_Jumlah", "Total_Keuntungan"]].values
    scaler  = MinMaxScaler()
    x_train = scaler.fit_transform(x_train)
    logs.append({
        "tahap":  "Feature Scaling (MinMaxScaler)",
        "detail": "MinMaxScaler diterapkan pada [Total_Jumlah, Total_Keuntungan] → rentang [0, 1].",
    })

    # ── Penentuan K optimal — Silhouette Score (cell-23) ─────────────────
    sil_scores: list = []
    k_range = range(2, min(10, len(x_train)))
    for k in k_range:
        km     = KMeans(n_clusters=k, random_state=42, n_init=10)
        labels = km.fit_predict(x_train)
        sil_scores.append(silhouette_score(x_train, labels))

    best_k   = list(k_range)[int(np.argmax(sil_scores))]
    best_sil = float(max(sil_scores))
    logs.append({
        "tahap":  "Penentuan K Optimal (Silhouette Score)",
        "detail": (
            f"K terbaik: {best_k} (Silhouette Score: {best_sil:.4f}). "
            f"Rentang K yang diuji: 2–{max(k_range)}."
        ),
    })

    # Elbow (inertia) untuk grafik saja
    inertias: list = []
    for k in k_range:
        km = KMeans(n_clusters=k, random_state=42, n_init=10)
        km.fit(x_train)
        inertias.append(km.inertia_)

    # ── K-Means clustering final (cell-24) ────────────────────────────────
    kmean = KMeans(n_clusters=best_k, random_state=42, n_init=10)
    df_total["Klaster"] = kmean.fit_predict(x_train)
    logs.append({
        "tahap":  "K-Means Clustering",
        "detail": f"K-Means dijalankan: K={best_k}, random_state=42, n_init=10.",
    })

    # ── Cluster summary — rata-rata per klaster (cell-26, 28) ─────────────
    cs = df_total.groupby("Klaster", as_index=False).agg(
        rata_jumlah=("Total_Jumlah", "mean"),
        rata_keuntungan=("Total_Keuntungan", "mean"),
    )
    cs.columns = ["Klaster", "Rata-rata Jumlah Penjualan", "Rata-rata Keuntungan"]

    # ── Kategorisasi berdasarkan Total_Jumlah (cell-31) ───────────────────
    def kategori(j: float) -> str:
        if j >= 400:
            return "Sangat Laris"
        elif j >= 350:
            return "Laris"
        elif j >= 200:
            return "Cukup"
        return "Kurang Laris"

    df_total["Kategori"] = df_total["Total_Jumlah"].apply(kategori)
    logs.append({
        "tahap":  "Kategorisasi Penjualan",
        "detail": (
            "Kategori berdasarkan Total_Jumlah: "
            "Sangat Laris (≥400), Laris (350–399), Cukup (200–349), Kurang Laris (<200)."
        ),
    })

    # ── Laporan hasil clustering (cell-27) ────────────────────────────────
    laporan_clustering = (
        df_total[["Nama Item", "Total_Jumlah", "Total_Keuntungan", "Klaster"]]
        .sort_values(["Klaster", "Total_Keuntungan"], ascending=[True, False])
        .reset_index(drop=True)
    )

    # ── Laporan kategorisasi (cell-32) ────────────────────────────────────
    laporan_kategori = (
        df_total[["Nama Item", "Total_Jumlah", "Total_Keuntungan", "Klaster", "Kategori"]]
        .sort_values(["Kategori", "Total_Jumlah"], ascending=[True, False])
        .reset_index(drop=True)
    )

    # ── Style matplotlib ──────────────────────────────────────────────────
    plt.rcParams.update({
        "font.family":       "DejaVu Sans",
        "axes.spines.top":   False,
        "axes.spines.right": False,
        "axes.titlesize":    13,
        "axes.titleweight":  "bold",
        "axes.titlepad":     14,
        "axes.labelsize":    11,
        "axes.labelpad":     8,
        "xtick.labelsize":   9,
        "ytick.labelsize":   9,
        "legend.fontsize":   9,
        "legend.title_fontsize": 10,
        "figure.facecolor":  "white",
        "axes.facecolor":    "#fafafa",
        "axes.grid":         True,
        "grid.color":        "#e5e7eb",
        "grid.linewidth":    0.8,
    })
    cluster_colors = ["royalblue", "orange", "green", "red", "purple", "brown", "pink", "gray"]

    # ── Chart 1: Rata-rata Jumlah Penjualan per Klaster (cell-29) ─────────
    fig1, ax1 = plt.subplots(figsize=(8, 5))
    bars1 = ax1.bar(
        cs["Klaster"].astype(str),
        cs["Rata-rata Jumlah Penjualan"],
        color=cluster_colors[: len(cs)],
        width=0.5,
    )
    for b in bars1:
        h = b.get_height()
        ax1.text(b.get_x() + b.get_width() / 2, h, f"{h:.1f}",
                 ha="center", va="bottom", fontsize=9)
    ax1.set_title("Rata-rata Jumlah Penjualan Menu pada Setiap Klaster")
    ax1.set_xlabel("Klaster")
    ax1.set_ylabel("Rata-rata Jumlah Penjualan")
    ax1.legend(
        handles=[Patch(facecolor=cluster_colors[i], label=f"Klaster {cs.iloc[i]['Klaster']}")
                 for i in range(len(cs))],
        title="Keterangan Warna",
    )
    fig1.tight_layout(pad=2)
    chart_bar_jumlah = fig_to_base64(fig1)

    # ── Chart 2: Rata-rata Keuntungan per Klaster (cell-30) ───────────────
    fig2, ax2 = plt.subplots(figsize=(8, 5))
    bars2 = ax2.bar(
        cs["Klaster"].astype(str),
        cs["Rata-rata Keuntungan"],
        color=cluster_colors[: len(cs)],
        width=0.5,
    )
    for b in bars2:
        h = b.get_height()
        ax2.text(b.get_x() + b.get_width() / 2, h, f"Rp{h:,.0f}",
                 ha="center", va="bottom", fontsize=8)
    ax2.set_title("Rata-rata Keuntungan Menu pada Setiap Klaster")
    ax2.set_xlabel("Klaster")
    ax2.set_ylabel("Rata-rata Keuntungan (Rp)")
    ax2.yaxis.set_major_formatter(
        plt.FuncFormatter(lambda x, _: f"Rp{x:,.0f}")
    )
    ax2.legend(
        handles=[Patch(facecolor=cluster_colors[i], label=f"Klaster {cs.iloc[i]['Klaster']}")
                 for i in range(len(cs))],
        title="Keterangan Warna",
    )
    fig2.tight_layout(pad=2)
    chart_bar_keuntungan = fig_to_base64(fig2)

    # ── Chart 3: Visualisasi Kategorisasi Penjualan Menu (cell-33) ────────
    warna_kat = {
        "Sangat Laris": "green",
        "Laris":        "royalblue",
        "Cukup":        "orange",
        "Kurang Laris": "red",
    }
    warna_batang = laporan_kategori["Kategori"].map(warna_kat)
    n_menu = len(laporan_kategori)
    fig3, ax3 = plt.subplots(figsize=(max(12, n_menu * 0.75), 6))
    bars3 = ax3.bar(
        laporan_kategori["Nama Item"],
        laporan_kategori["Total_Jumlah"],
        color=warna_batang,
        width=0.6,
    )
    for b in bars3:
        h = b.get_height()
        ax3.text(b.get_x() + b.get_width() / 2, h, f"{h:.0f}",
                 ha="center", va="bottom", fontsize=8)
    ax3.set_title("Visualisasi Kategorisasi Penjualan Menu Cafe")
    ax3.set_xlabel("Nama Menu")
    ax3.set_ylabel("Total Jumlah Penjualan")
    ax3.tick_params(axis="x", rotation=60)
    ax3.set_xticklabels(ax3.get_xticklabels(), ha="right")
    ax3.legend(
        handles=[
            Patch(facecolor="green",     label="Sangat Laris (≥ 400)"),
            Patch(facecolor="royalblue", label="Laris (350–399)"),
            Patch(facecolor="orange",    label="Cukup (200–349)"),
            Patch(facecolor="red",       label="Kurang Laris (< 200)"),
        ],
        title="Kategori Jumlah Penjualan",
        loc="upper right",
    )
    fig3.tight_layout(pad=2)
    chart_kategorisasi = fig_to_base64(fig3)

    # ── Chart 4: Elbow Method ──────────────────────────────────────────────
    fig4, ax4 = plt.subplots(figsize=(8, 5))
    ax4.plot(list(k_range), inertias, marker="o", markersize=8, color="#6366f1",
             linewidth=2.5, markerfacecolor="white", markeredgewidth=2)
    ax4.axvline(x=best_k, color="#ef4444", linestyle="--",
                linewidth=1.8, alpha=0.8, label=f"K optimal = {best_k}")
    best_inertia = inertias[list(k_range).index(best_k)]
    ax4.annotate(
        f"  K={best_k}\n  Inertia={best_inertia:.4f}",
        xy=(best_k, best_inertia),
        xytext=(best_k + 0.4, best_inertia + (max(inertias) - min(inertias)) * 0.08),
        fontsize=9, color="#ef4444",
        arrowprops=dict(arrowstyle="->", color="#ef4444", lw=1.2),
    )
    ax4.set_xlabel("Jumlah Klaster (K)")
    ax4.set_ylabel("Inertia (Sum of Squared Error)")
    ax4.set_title("Elbow Method — Penentuan Jumlah Klaster Optimal")
    ax4.set_xticks(list(k_range))
    ax4.legend(framealpha=0.9, edgecolor="#e5e7eb", fancybox=False)
    fig4.tight_layout(pad=2)
    chart_elbow = fig_to_base64(fig4)

    # ── Chart 5: Silhouette Score per K ───────────────────────────────────
    fig5, ax5 = plt.subplots(figsize=(8, 5))
    ax5.plot(list(k_range), sil_scores, marker="s", markersize=8, color="#059669",
             linewidth=2.5, markerfacecolor="white", markeredgewidth=2)
    ax5.axvline(x=best_k, color="#ef4444", linestyle="--",
                linewidth=1.8, alpha=0.8, label=f"K optimal = {best_k}")
    sil_range = max(sil_scores) - min(sil_scores) if len(sil_scores) > 1 else 0.1
    ax5.annotate(
        f"  K={best_k}\n  Score={best_sil:.4f}",
        xy=(best_k, best_sil),
        xytext=(best_k + 0.4, best_sil - sil_range * 0.15),
        fontsize=9, color="#ef4444",
        arrowprops=dict(arrowstyle="->", color="#ef4444", lw=1.2),
    )
    ax5.set_xlabel("Jumlah Klaster (K)")
    ax5.set_ylabel("Silhouette Score")
    ax5.set_title("Silhouette Score per K — Kualitas Pengelompokan")
    ax5.set_xticks(list(k_range))
    ax5.legend(framealpha=0.9, edgecolor="#e5e7eb", fancybox=False)
    fig5.tight_layout(pad=2)
    chart_silhouette = fig_to_base64(fig5)

    # ── Susun output ──────────────────────────────────────────────────────
    cluster_summary_out = cs.to_dict(orient="records")
    for r in cluster_summary_out:
        r["Klaster"]                     = int(r["Klaster"])
        r["Rata-rata Jumlah Penjualan"]  = round(float(r["Rata-rata Jumlah Penjualan"]), 2)
        r["Rata-rata Keuntungan"]        = round(float(r["Rata-rata Keuntungan"]), 2)

    table_rows = laporan_clustering.to_dict(orient="records")
    for r in table_rows:
        r["Total_Jumlah"]     = float(r["Total_Jumlah"])
        r["Total_Keuntungan"] = float(r["Total_Keuntungan"])
        r["Klaster"]          = int(r["Klaster"])

    kategorisasi_rows = laporan_kategori.to_dict(orient="records")
    for r in kategorisasi_rows:
        r["Total_Jumlah"]     = float(r["Total_Jumlah"])
        r["Total_Keuntungan"] = float(r["Total_Keuntungan"])
        r["Klaster"]          = int(r["Klaster"])

    return {
        "status":             "success",
        "best_k":             best_k,
        "silhouette_score":   round(best_sil, 4),
        "total_menu":         int(len(df_total)),
        "date_range":         {"from": str(min_date.date()), "to": str(max_date.date())},
        "preprocessing_logs": logs,
        "table_rows":         table_rows,
        "kategorisasi_rows":  kategorisasi_rows,
        "cluster_summary":    cluster_summary_out,
        "charts": {
            "bar_jumlah":     chart_bar_jumlah,
            "bar_keuntungan": chart_bar_keuntungan,
            "kategorisasi":   chart_kategorisasi,
            "bar":            chart_kategorisasi,   # backward-compat alias
            "elbow":          chart_elbow,
            "silhouette":     chart_silhouette,
        },
    }


# ── ENDPOINTS ──────────────────────────────────────────────────────────────
@app.get("/health")
def health():
    return {"status": "ok", "service": "W9 Cafe Data Mining API v2"}


@app.get("/preview-data")
def preview_data():
    try:
        df = fetch_order_data()
        if df.empty:
            return {"total_rows": 0, "total_menu": 0, "date_range": {}, "sample": []}
        return {
            "total_rows": len(df),
            "total_menu": df["Nama Item"].nunique(),
            "date_range": {"from": str(df["Tanggal"].min().date()),
                           "to":   str(df["Tanggal"].max().date())},
            "sample": df.head(10).to_dict(orient="records"),
        }
    except Exception as e:
        return {"status": "error", "message": str(e)}


@app.post("/clustering")
async def clustering(request: Request):
    try:
        try:
            body = await request.json()
        except Exception:
            body = {}

        date_from = (body.get("date_from") or "").strip() or None
        date_to   = (body.get("date_to")   or "").strip() or None

        print(f"[clustering] filter date_from={date_from!r}  date_to={date_to!r}")

        df = fetch_order_data(date_from, date_to)
        if df.empty:
            return {
                "status":          "error",
                "message":         "Tidak ada data pesanan selesai pada rentang tanggal yang dipilih.",
                "filter_date_from": date_from,
                "filter_date_to":   date_to,
            }
        if df["Nama Item"].nunique() < 2:
            return {
                "status":  "error",
                "message": f"Clustering butuh minimal 2 menu berbeda. Hanya ada {df['Nama Item'].nunique()} menu.",
            }
        result = run_pipeline(df)
        result["filter_date_from"] = date_from
        result["filter_date_to"]   = date_to
        return result
    except Exception as e:
        import traceback
        return {"status": "error", "message": str(e), "trace": traceback.format_exc()}


@app.post("/prediction")
async def prediction(request: Request):
    try:
        try:
            body = await request.json()
        except Exception:
            body = {}

        date_from = (body.get("date_from") or "").strip() or None
        date_to   = (body.get("date_to")   or "").strip() or None

        print(f"[prediction] filter date_from={date_from!r}  date_to={date_to!r}")

        df = fetch_order_data(date_from, date_to)
        if df.empty:
            return {"status": "error", "message": "Tidak ada data pesanan selesai pada rentang tanggal yang dipilih."}
        unique_dates = df["Tanggal"].nunique()
        if unique_dates < 3:
            return {"status": "error",
                    "message": f"Data terlalu sedikit: hanya {unique_dates} hari transaksi. Butuh minimal 3 hari."}
        return run_prediction_pipeline(df)
    except Exception as e:
        import traceback
        return {"status": "error", "message": str(e), "trace": traceback.format_exc()}


def fetch_association_data(date_from: Optional[str] = None,
                           date_to:   Optional[str] = None) -> pd.DataFrame:
    """Fetch order items WITH item_position for sequential/directed association rules."""
    where_clauses = ["o.status = 'selesai'"]
    params: list = []

    if date_from and date_from.strip():
        where_clauses.append("o.created_at::date >= %s")
        params.append(date_from.strip())
    if date_to and date_to.strip():
        where_clauses.append("o.created_at::date <= %s")
        params.append(date_to.strip())

    where_sql = " AND ".join(where_clauses)

    sql = f"""
        SELECT
            o.created_at::date   AS "Tanggal",
            o.order_code         AS "Order_id",
            m.name               AS "Nama Item",
            oi.item_position     AS "Posisi",
            oi.quantity          AS "Jumlah",
            oi.unit_price::float AS "Harga",
            oi.subtotal::float   AS "Subtotal"
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        JOIN menus  m ON m.id = oi.menu_id
        WHERE {where_sql}
        ORDER BY o.created_at ASC, o.id ASC, oi.item_position ASC
    """
    conn = get_connection()
    try:
        with conn.cursor() as cur:
            cur.execute(sql, params if params else None)
            rows = cur.fetchall()
    finally:
        conn.close()

    df = pd.DataFrame([dict(r) for r in rows])
    if not df.empty:
        df["Tanggal"] = pd.to_datetime(df["Tanggal"])
    return df


@app.post("/association")
async def association(request: Request):
    try:
        try:
            body = await request.json()
        except Exception:
            body = {}
        date_from = (body.get("date_from") or "").strip() or None
        date_to   = (body.get("date_to")   or "").strip() or None

        print(f"[association] filter date_from={date_from!r}  date_to={date_to!r}")

        df = fetch_association_data(date_from, date_to)
        if df.empty:
            return {
                "status":          "error",
                "message":         "Tidak ada data pesanan pada rentang tanggal tersebut.",
                "filter_date_from": date_from,
                "filter_date_to":   date_to,
            }
        result = run_association_pipeline(df)
        # Inject the requested filter dates into the response so the UI can verify
        result["filter_date_from"] = date_from
        result["filter_date_to"]   = date_to
        return result
    except Exception as e:
        import traceback
        return {"status": "error", "message": str(e), "trace": traceback.format_exc()}


def fetch_ingredient_data(date_from: Optional[str] = None,
                          date_to:   Optional[str] = None) -> pd.DataFrame:
    where_clauses = []
    params: list = []

    if date_from and date_from.strip():
        where_clauses.append("diu.usage_date::date >= %s")
        params.append(date_from.strip())
    if date_to and date_to.strip():
        where_clauses.append("diu.usage_date::date <= %s")
        params.append(date_to.strip())

    where_sql = ("WHERE " + " AND ".join(where_clauses)) if where_clauses else ""

    sql = f"""
        SELECT
            diu.usage_date::date             AS "Tanggal",
            diu.ingredient_name              AS "Bahan_Baku",
            diu.unit                         AS "Unit",
            diu.jumlah_digunakan::float      AS "Jumlah_Digunakan"
        FROM daily_ingredient_usages diu
        {where_sql}
        ORDER BY diu.usage_date ASC
    """
    conn = get_connection()
    try:
        with conn.cursor() as cur:
            cur.execute(sql, params if params else None)
            rows = cur.fetchall()
    finally:
        conn.close()
    df = pd.DataFrame([dict(r) for r in rows])
    if not df.empty:
        df["Tanggal"] = pd.to_datetime(df["Tanggal"])
    return df


@app.post("/clustering-bahan-baku")
async def clustering_bahan_baku(request: Request):
    try:
        try:
            body = await request.json()
        except Exception:
            body = {}
        date_from = (body.get("date_from") or "").strip() or None
        date_to   = (body.get("date_to")   or "").strip() or None

        print(f"[clustering-bahan-baku] date_from={date_from!r}  date_to={date_to!r}")

        df = fetch_ingredient_data(date_from, date_to)
        if df.empty:
            return {"status": "error", "message": "Tidak ada data pemakaian bahan baku pada rentang tanggal yang dipilih."}
        return run_bahan_baku_pipeline(df)
    except Exception as e:
        import traceback
        return {"status": "error", "message": str(e), "trace": traceback.format_exc()}


@app.post("/prediction-bahan-baku")
async def prediction_bahan_baku(request: Request):
    try:
        try:
            body = await request.json()
        except Exception:
            body = {}
        date_from = (body.get("date_from") or "").strip() or None
        date_to   = (body.get("date_to")   or "").strip() or None

        print(f"[prediction-bahan-baku] date_from={date_from!r}  date_to={date_to!r}")

        df = fetch_ingredient_data(date_from, date_to)
        if df.empty:
            return {"status": "error", "message": "Tidak ada data pemakaian bahan baku pada rentang tanggal yang dipilih."}
        unique_dates = df["Tanggal"].nunique()
        if unique_dates < 3:
            return {"status": "error",
                    "message": f"Data terlalu sedikit: hanya {unique_dates} hari data. Butuh minimal 3 hari."}
        return run_prediction_pipeline_bahan_baku(df)
    except Exception as e:
        import traceback
        return {"status": "error", "message": str(e), "trace": traceback.format_exc()}
