export default function CategoryChip({ label, active, onClick }) {
    return (
        <button
            onClick={onClick}
            style={{
                height: 38,
                padding: '0 18px',
                borderRadius: 16,
                border: `1px solid ${active ? '#E8763A' : '#EDE8E2'}`,
                background: active ? '#E8763A' : '#FFFFFF',
                color: active ? '#FFFFFF' : '#8C7B6B',
                fontSize: 12,
                fontWeight: active ? 700 : 600,
                cursor: 'pointer',
                whiteSpace: 'nowrap',
                flexShrink: 0,
                boxShadow: active ? '0 3px 10px rgba(232,118,58,0.25)' : 'none',
                transition: 'all 0.15s',
            }}
        >
            {label}
        </button>
    );
}
