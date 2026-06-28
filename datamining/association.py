"""
Association Rule Mining — FP-Growth (Dua Arah)
W9 Cafe POS | Capstone STIE Totalwin

Menggunakan mlxtend FP-Growth + TransactionEncoder + association_rules.
A→B dan B→A di-generate sebagai rule TERPISAH dengan nilai support/confidence/lift berbeda.
Filter: hanya rule 1-itemset → 1-itemset.
Diurutkan berdasarkan lift tertinggi, diambil TOP 8.
"""

import io, base64, warnings
import pandas as pd
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from mlxtend.frequent_patterns import fpgrowth, association_rules as mlxtend_rules
from mlxtend.preprocessing import TransactionEncoder

warnings.filterwarnings("ignore")

MIN_SUPPORT    = 0.01
MIN_CONFIDENCE = 0.01


# ── Preprocessing ──────────────────────────────────────────────────────────────
def preprocess(df: pd.DataFrame):
    logs = []
    df = df.copy()
    df["Tanggal"] = pd.to_datetime(df["Tanggal"])

    logs.append({
        "tahap":  "Load Data Riwayat Pesanan",
        "detail": (
            f"Berhasil memuat {len(df)} baris item pesanan dari "
            f"{df['Nama Item'].nunique()} menu unik. "
            f"Rentang: {df['Tanggal'].min().date()} s/d {df['Tanggal'].max().date()}."
        ),
    })

    return df, logs


# ── Main Pipeline ──────────────────────────────────────────────────────────────
def run_association_pipeline(df: pd.DataFrame) -> dict:
    if "Order_id" in df.columns and "ID Pesanan" not in df.columns:
        df = df.rename(columns={"Order_id": "ID Pesanan"})

    df_clean, logs = preprocess(df)
    date_from = str(df_clean["Tanggal"].min().date())
    date_to   = str(df_clean["Tanggal"].max().date())

    # ── Kelompokkan item per transaksi ─────────────────────────────────────
    # Gunakan semua transaksi (termasuk yang hanya 1 item)
    transactions = (
        df_clean
        .groupby("ID Pesanan")["Nama Item"]
        .apply(list)
        .reset_index()
    )

    logs.append({
        "tahap":  "Pengelompokan Transaksi",
        "detail": (
            f"Total transaksi (order): {len(transactions)}. "
            f"Total baris item: {len(df_clean)}."
        ),
    })

    # ── Encoding dengan TransactionEncoder ────────────────────────────────
    te = TransactionEncoder()
    te_array   = te.fit(transactions["Nama Item"]).transform(transactions["Nama Item"])
    df_encoded = pd.DataFrame(te_array, columns=te.columns_)

    total_transaksi = df_encoded.shape[0]

    logs.append({
        "tahap":  "Encoding Transaksi (TransactionEncoder)",
        "detail": (
            f"Ditemukan {df_encoded.shape[1]} menu unik. "
            f"Matrix transaksi: {df_encoded.shape[0]} transaksi × {df_encoded.shape[1]} menu. "
            f"Urutan item dalam transaksi diabaikan — fokus pada kombinasi (dua arah)."
        ),
    })

    # ── FP-Growth: Frequent Itemsets ───────────────────────────────────────
    frequent_itemsets = fpgrowth(df_encoded, min_support=MIN_SUPPORT, use_colnames=True, max_len=2)

    # Frequent 1-itemsets
    freq1_df = frequent_itemsets[
        frequent_itemsets["itemsets"].apply(lambda x: len(x) == 1)
    ].copy()
    freq1_df["item"]             = freq1_df["itemsets"].apply(lambda x: list(x)[0])
    freq1_df["jumlah_kemunculan"] = (freq1_df["support"] * total_transaksi).round().astype(int)
    freq1_list = (
        freq1_df[["item", "support", "jumlah_kemunculan"]]
        .sort_values("jumlah_kemunculan", ascending=False)
        .to_dict("records")
    )
    for r in freq1_list:
        r["support"] = round(r["support"], 6)

    logs.append({
        "tahap":  "Frequent 1-Itemsets (FP-Growth)",
        "detail": f"Ditemukan {len(freq1_list)} item dengan min_support ≥ 1%.",
    })

    # Frequent 2-itemsets (pasangan tidak berurutan)
    freq2_df = frequent_itemsets[
        frequent_itemsets["itemsets"].apply(lambda x: len(x) == 2)
    ].copy()
    freq2_df["items"]            = freq2_df["itemsets"].apply(lambda x: " + ".join(sorted(list(x))))
    freq2_df["jumlah_kemunculan"] = (freq2_df["support"] * total_transaksi).round().astype(int)
    freq2_list = (
        freq2_df[["items", "support", "jumlah_kemunculan"]]
        .sort_values("jumlah_kemunculan", ascending=False)
        .to_dict("records")
    )
    for r in freq2_list:
        r["support"] = round(r["support"], 6)

    logs.append({
        "tahap":  "Frequent 2-Itemsets (FP-Growth)",
        "detail": (
            f"Ditemukan {len(freq2_list)} pasangan menu yang sering dibeli bersamaan "
            f"dengan min_support ≥ 1%."
        ),
    })

    # ── Association Rules (dua arah) ───────────────────────────────────────
    # mlxtend otomatis generate A→B dan B→A sebagai rule terpisah
    rules_df = mlxtend_rules(
        frequent_itemsets,
        metric="confidence",
        min_threshold=MIN_CONFIDENCE,
    )

    # Filter hanya 1→1 rules
    rules_2_items = rules_df[
        (rules_df["antecedents"].apply(len) == 1) &
        (rules_df["consequents"].apply(len) == 1)
    ].copy()

    rules_2_items["menu_pertama"]        = rules_2_items["antecedents"].apply(lambda x: list(x)[0])
    rules_2_items["menu_kedua"]          = rules_2_items["consequents"].apply(lambda x: list(x)[0])
    rules_2_items["jumlah_menu_pertama"] = (rules_2_items["antecedent support"] * total_transaksi).round().astype(int)
    rules_2_items["jumlah_menu_kedua"]   = (rules_2_items["consequent support"]  * total_transaksi).round().astype(int)
    rules_2_items["jumlah_bersamaan"]    = (rules_2_items["support"] * total_transaksi).round().astype(int)

    # Urutkan berdasarkan lift tertinggi, ambil TOP 8
    rules_final = rules_2_items.sort_values("lift", ascending=False).head(8)

    rules_top = []
    for _, row in rules_final.iterrows():
        menu1    = row["menu_pertama"]
        menu2    = row["menu_kedua"]
        supp_pct = round(float(row["support"])    * 100, 2)
        conf_pct = round(float(row["confidence"]) * 100, 2)
        lift_val = round(float(row["lift"]), 2)

        rules_top.append({
            "menu_pertama":        menu1,
            "menu_kedua":          menu2,
            "jumlah_menu_pertama": int(row["jumlah_menu_pertama"]),
            "jumlah_menu_kedua":   int(row["jumlah_menu_kedua"]),
            "jumlah_bersamaan":    int(row["jumlah_bersamaan"]),
            "support":             round(float(row["support"]),    6),
            "confidence":          round(float(row["confidence"]), 6),
            "lift":                round(float(row["lift"]),       6),
            "interpretasi": (
                f"Ada sekitar {supp_pct}% transaksi pembelian {menu1} dan {menu2} secara bersamaan. "
                f"Dari semua yang membeli {menu1}, ada sekitar {conf_pct}% juga yang membeli {menu2} secara bersamaan. "
                f"Selain itu, kemungkinan membeli {menu2} menjadi {lift_val} kali lebih besar "
                f"jika seseorang membeli {menu1}."
            ),
        })

    total_rules_found = len(rules_2_items)
    logs.append({
        "tahap":  "Association Rules (Dua Arah)",
        "detail": (
            f"Total rules 1→1 ditemukan: {total_rules_found} "
            f"(A→B dan B→A adalah rule TERPISAH dengan nilai berbeda). "
            f"Diambil TOP 8 berdasarkan lift tertinggi."
        ),
    })

    min_conf_val = rules_top[-1]["confidence"] if rules_top else MIN_CONFIDENCE

    # ── Charts ─────────────────────────────────────────────────────────────
    plt.rcParams.update({
        "font.family":       "DejaVu Sans",
        "axes.spines.top":   False,
        "axes.spines.right": False,
        "figure.facecolor":  "white",
        "axes.facecolor":    "#fafafa",
        "axes.grid":         True,
        "grid.color":        "#e5e7eb",
        "grid.linewidth":    0.8,
    })

    def _to_b64(fig) -> str:
        buf = io.BytesIO()
        fig.savefig(buf, format="png", bbox_inches="tight", dpi=120)
        buf.seek(0)
        enc = base64.b64encode(buf.read()).decode()
        plt.close(fig)
        return enc

    # Chart 1: Top 8 Rules by Lift (horizontal bar)
    chart_top_rules = None
    if rules_top:
        labels    = [f"{r['menu_pertama']} → {r['menu_kedua']}" for r in rules_top]
        lift_vals = [r["lift"] for r in rules_top]
        colors    = ["#4f46e5" if v >= 1.5 else "#f59e0b" for v in lift_vals]

        fig1, ax1 = plt.subplots(figsize=(11, max(4, len(labels) * 0.7)))
        bars = ax1.barh(labels[::-1], lift_vals[::-1], color=colors[::-1], height=0.6)
        ax1.axvline(x=1.0, color="#9ca3af", linestyle="--", linewidth=1.2, alpha=0.7)
        for bar, val in zip(bars, lift_vals[::-1]):
            ax1.text(val + 0.01, bar.get_y() + bar.get_height() / 2,
                     f"{val:.2f}", va="center", fontsize=9, color="#374151")
        ax1.set_xlabel("Lift Value")
        ax1.set_title(
            "Top 8 Association Rules — Lift Tertinggi\n"
            "(A → B dan B → A adalah rule berbeda dengan nilai yang berbeda)",
            fontsize=11, fontweight="bold", pad=12,
        )
        ax1.xaxis.grid(True)
        ax1.yaxis.grid(False)
        fig1.tight_layout(pad=2)
        chart_top_rules = _to_b64(fig1)

    # Chart 2: Support vs Confidence scatter
    chart_sup_conf = None
    if not rules_2_items.empty:
        sup_vals      = rules_2_items["support"].tolist()
        conf_vals     = rules_2_items["confidence"].tolist()
        lift_vals_all = rules_2_items["lift"].tolist()

        fig2, ax2 = plt.subplots(figsize=(7, 5))
        sc = ax2.scatter(
            sup_vals, conf_vals,
            c=lift_vals_all, cmap="YlOrRd",
            s=80, alpha=0.7, edgecolors="#cbd5e1", linewidths=0.5,
        )
        plt.colorbar(sc, ax=ax2, label="Lift")
        ax2.set_xlabel("Support")
        ax2.set_ylabel("Confidence")
        ax2.set_title(
            "Support vs Confidence (warna = Lift)\nSeluruh Rules A → B (dua arah)",
            fontsize=11, fontweight="bold", pad=10,
        )
        fig2.tight_layout(pad=2)
        chart_sup_conf = _to_b64(fig2)

    # Chart 3: Top 15 Frequent 1-itemsets
    chart_freq_item = None
    if freq1_list:
        top_items  = freq1_list[:15]
        item_names = [f["item"]              for f in top_items]
        item_cnts  = [f["jumlah_kemunculan"] for f in top_items]

        fig3, ax3 = plt.subplots(figsize=(10, max(4, len(item_names) * 0.5)))
        ax3.barh(item_names[::-1], item_cnts[::-1], color="#6366f1", height=0.6)
        for i, cnt in enumerate(item_cnts[::-1]):
            ax3.text(cnt + 0.3, i, str(cnt), va="center", fontsize=9, color="#374151")
        ax3.set_xlabel("Jumlah Kemunculan dalam Transaksi")
        ax3.set_title(
            "Frequent 1-Itemsets — Frekuensi Kemunculan per Menu",
            fontsize=12, fontweight="bold", pad=10,
        )
        ax3.xaxis.grid(True)
        ax3.yaxis.grid(False)
        fig3.tight_layout(pad=2)
        chart_freq_item = _to_b64(fig3)

    return {
        "status":             "success",
        "total_rules":        len(rules_top),
        "total_transactions": total_transaksi,
        "min_support":        MIN_SUPPORT,
        "min_confidence":     round(min_conf_val, 6),
        "date_range":         {"from": date_from, "to": date_to},
        "rules":              rules_top,
        "freq_1_itemsets":    freq1_list,
        "freq_2_itemsets":    freq2_list[:20],
        "preprocessing_logs": logs,
        "charts": {
            "top_rules": chart_top_rules,
            "sup_conf":  chart_sup_conf,
            "freq_item": chart_freq_item,
        },
    }
