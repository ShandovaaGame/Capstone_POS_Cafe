// Pencil design tokens — brown/coffee theme
const statusMap = {
    // Order statuses
    pending:  { dot: '#D4A64A', text: '#D4A64A', bg: '#FFF8E1', label: 'Pending'  },
    diproses: { dot: '#5B8BD4', text: '#5B8BD4', bg: '#E3F2FD', label: 'Diproses' },
    selesai:  { dot: '#4D9B6A', text: '#4D9B6A', bg: '#E8F5E9', label: 'Selesai'  },
    // Verifikasi statuses
    menunggu:  { dot: '#D4A64A', text: '#D4A64A', bg: '#FFF8E1', label: 'Menunggu'  },
    disetujui: { dot: '#4D9B6A', text: '#4D9B6A', bg: '#E8F5E9', label: 'Disetujui' },
    ditolak:   { dot: '#C95D4A', text: '#C95D4A', bg: '#FBE9E7', label: 'Ditolak'   },
    dibatalkan: { dot: '#DC3545', text: '#DC3545', bg: '#FBE9E7', label: 'Dibatalkan' },
};

export default function StatusBadge({ status }) {
    const s = statusMap[status] ?? { dot: '#9C9B99', text: '#6D6C6A', bg: '#F5F4F1', label: status };
    return (
        <span style={{
            display: 'inline-flex', alignItems: 'center', gap: 6,
            background: s.bg,
            borderRadius: 100,
            padding: '0 10px',
            height: 28,
            fontSize: 12, fontWeight: 600,
            color: s.text,
            fontFamily: 'Outfit, system-ui',
            whiteSpace: 'nowrap',
        }}>
            <span style={{
                width: 6, height: 6, borderRadius: '50%',
                background: s.dot, flexShrink: 0,
                display: 'inline-block',
            }} />
            {s.label}
        </span>
    );
}
