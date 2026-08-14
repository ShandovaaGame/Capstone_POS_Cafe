import { useState, useEffect } from 'react';
import { router, Head } from '@inertiajs/react';
import { User, Phone, Check, MapPin } from 'lucide-react';
import PelangganLayout from '@/Layouts/PelangganLayout';

const F = '"Inter", system-ui, sans-serif';
const C = {
    bg:          '#F7F5F2',
    surface:     '#FFFFFF',
    border:      '#E2DED8',
    accent:      '#44403C',
    accentHover: '#292524',
    textPrimary: '#1C1917',
    textSecond:  '#78716C',
    textMuted:   '#A8A29E',
    headerBg:    '#1E3A4C',
    headerDark:  '#112D3E',
};

export default function Identitas({ table }) {
    const [name,        setName]        = useState('');
    const [phone,       setPhone]       = useState('');
    const [isMahasiswa, setIsMahasiswa] = useState(false);
    const [nameError,   setNameError]   = useState('');
    const [phoneError,  setPhoneError]  = useState('');

    /* Jika sudah pernah isi identitas untuk meja ini → skip ke menu */
    useEffect(() => {
        if (!table) return;
        try {
            const saved = sessionStorage.getItem('w9_customer');
            if (saved) {
                const data = JSON.parse(saved);
                if (data.name && data.phone && data.tableId === table.id) {
                    router.visit(`/pelanggan/menu?table=${table.id}`);
                }
            }
        } catch (_) {}
    }, []);

    function handleLanjut() {
        let valid = true;

        if (!name.trim() || name.trim().length < 2) {
            setNameError('Nama minimal 2 karakter');
            valid = false;
        } else {
            setNameError('');
        }

        const phoneClean = phone.replace(/\D/g, '');
        if (!phoneClean || phoneClean.length < 10 || phoneClean.length > 15) {
            setPhoneError('Nomor telepon tidak valid (min 10 digit)');
            valid = false;
        } else {
            setPhoneError('');
        }

        if (!valid) return;

        sessionStorage.setItem('w9_customer', JSON.stringify({
            name:        name.trim(),
            phone:       phoneClean,
            isMahasiswa: isMahasiswa,
            tableId:     table?.id ?? null,
            tableNumber: table?.table_number ?? null,
        }));

        router.visit(table ? `/pelanggan/menu?table=${table.id}` : '/pelanggan/menu');
    }

    /* ── No table state ── */
    if (!table) {
        return (
            <PelangganLayout activeTab="menu" showBottomNav={false}>
                <Head>
                    <title>W9 Cafe</title>
                    <link rel="preconnect" href="https://fonts.googleapis.com" />
                    <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" />
                </Head>

                {/* Wallpaper */}
                <div style={{
                    position: 'fixed', inset: 0,
                    zIndex: 0, pointerEvents: 'none',
                }}>
                    <img src="/images/wallpaper-menu.jpg" alt=""
                        style={{ width: '100%', height: '100%', objectFit: 'cover', objectPosition: 'center' }} />
                </div>

                <div style={{
                    position: 'relative', zIndex: 1,
                    minHeight: '100vh', display: 'flex', flexDirection: 'column',
                    alignItems: 'center', justifyContent: 'center',
                    padding: '40px 28px', fontFamily: F, textAlign: 'center',
                    background: 'rgba(247,245,242,0.85)', backdropFilter: 'blur(4px)',
                }}>
                    <div style={{
                        width: 96, height: 96, borderRadius: 20,
                        overflow: 'hidden', background: C.headerDark,
                        boxShadow: '0 8px 24px rgba(0,0,0,0.25)', marginBottom: 24,
                    }}>
                        <img src="/images/logo.jpg" alt="W9 Cafe"
                            style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                            onError={e => { e.target.style.display = 'none'; }} />
                    </div>
                    <h1 style={{ fontSize: 22, fontWeight: 700, color: C.textPrimary, margin: '0 0 10px', fontFamily: F }}>
                        Scan QR Meja
                    </h1>
                    <p style={{ fontSize: 14, color: C.textSecond, lineHeight: 1.6, margin: '0 0 6px', fontFamily: F }}>
                        Silakan scan QR code yang ada di meja Anda untuk mulai memesan.
                    </p>
                    <p style={{ fontSize: 12, color: C.textMuted, fontFamily: F }}>
                        Hubungi kasir jika membutuhkan bantuan.
                    </p>
                </div>
            </PelangganLayout>
        );
    }

    /* ── Main form ── */
    return (
        <PelangganLayout activeTab="menu" showBottomNav={false}>
            <Head>
                <title>Selamat Datang — W9 Cafe</title>
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" />
                <style>{`
                    html, body { background: ${C.bg}; }
                    .w9id-input { outline: none; transition: border-color 0.15s; }
                    .w9id-input:focus { border-color: ${C.accent} !important; }
                    .w9id-btn:active { background: ${C.accentHover} !important; }
                `}</style>
            </Head>

            {/* Wallpaper */}
            <div style={{
                position: 'fixed', top: 0, left: '50%', transform: 'translateX(-50%)',
                width: '100%', maxWidth: 430, height: '100vh',
                zIndex: 0, pointerEvents: 'none', overflow: 'hidden',
            }}>
                <img src="/images/wallpaper-identitas.png" alt=""
                    style={{ width: '100%', height: '100%', objectFit: 'cover', objectPosition: 'center' }} />
            </div>

            {/* Page */}
            <div style={{
                position: 'relative', zIndex: 1,
                minHeight: '100vh', display: 'flex', flexDirection: 'column',
                maxWidth: 430, margin: '0 auto',
            }}>

                {/* ── Branding header ── */}
                <header style={{
                    background: C.headerBg,
                    paddingTop: 48, paddingBottom: 32,
                    borderRadius: '0 0 36px 36px',
                    display: 'flex', flexDirection: 'column', alignItems: 'center',
                    flexShrink: 0,
                }}>
                    <div style={{
                        width: 96, height: 96, borderRadius: 20,
                        overflow: 'hidden', background: C.headerDark,
                        boxShadow: '0 8px 30px rgba(0,0,0,0.35)',
                        border: '1px solid rgba(255,255,255,0.10)',
                    }}>
                        <img src="/images/logo.jpg" alt="W9 Cafe"
                            style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                            onError={e => { e.target.style.display = 'none'; }} />
                    </div>

                    {/* Divider dekoratif bawah header */}
                    <div style={{
                        display: 'flex', alignItems: 'center', gap: 6,
                        marginTop: 24,
                    }}>
                        <div style={{ width: 32, height: 1, background: 'rgba(255,255,255,0.20)' }} />
                        <div style={{ width: 5, height: 5, borderRadius: '50%', background: 'rgba(255,255,255,0.40)' }} />
                        <div style={{ width: 24, height: 2, borderRadius: 1, background: 'rgba(255,255,255,0.70)' }} />
                        <div style={{ width: 5, height: 5, borderRadius: '50%', background: 'rgba(255,255,255,0.40)' }} />
                        <div style={{ width: 32, height: 1, background: 'rgba(255,255,255,0.20)' }} />
                    </div>
                </header>

                {/* ── Main content ── */}
                <main style={{
                    flex: 1,
                    background: 'transparent',
                    padding: '32px 24px 48px',
                    overflowY: 'auto',
                }}>

                    {/* Welcome + table pill */}
                    <div style={{ textAlign: 'center', marginBottom: 32 }}>
                        <h1 style={{
                            fontSize: 32, fontWeight: 700, color: C.accent,
                            textTransform: 'uppercase', letterSpacing: '-0.02em',
                            fontFamily: F, margin: '0 0 12px',
                        }}>
                            Selamat Datang!
                        </h1>
                        <div style={{
                            display: 'inline-flex', alignItems: 'center', gap: 6,
                            background: C.accent, color: '#FFFFFF',
                            borderRadius: 999, padding: '6px 16px',
                            boxShadow: '0 2px 8px rgba(68,64,60,0.25)',
                        }}>
                            <MapPin size={12} color="#FFFFFF" strokeWidth={2.5} />
                            <span style={{
                                fontSize: 11, fontWeight: 700,
                                letterSpacing: '0.10em', textTransform: 'uppercase',
                                fontFamily: F,
                            }}>
                                Meja No. {table.table_number}
                            </span>
                        </div>
                    </div>

                    {/* Form card */}
                    <section style={{
                        background: C.surface,
                        borderRadius: 12, padding: 24,
                        border: `1px solid ${C.border}`,
                        boxShadow: '0 4px 6px rgba(0,0,0,0.05), 0 2px 4px rgba(0,0,0,0.03)',
                        position: 'relative', overflow: 'hidden',
                        display: 'flex', flexDirection: 'column', gap: 20,
                    }}>
                        {/* Decorative corners */}
                        <div style={{
                            position: 'absolute', top: 0, right: 0,
                            width: 64, height: 64,
                            borderTop: `2px solid rgba(226,222,216,0.40)`,
                            borderRight: `2px solid rgba(226,222,216,0.40)`,
                            borderRadius: '0 12px 0 0',
                        }}/>
                        <div style={{
                            position: 'absolute', bottom: 0, left: 0,
                            width: 64, height: 64,
                            borderBottom: `2px solid rgba(226,222,216,0.40)`,
                            borderLeft: `2px solid rgba(226,222,216,0.40)`,
                            borderRadius: '0 0 0 12px',
                        }}/>

                        {/* ── Nama ── */}
                        <div>
                            <label style={{
                                display: 'block', fontSize: 13, fontWeight: 700,
                                color: C.textPrimary, marginBottom: 8, fontFamily: F,
                            }}>
                                Nama
                            </label>
                            <div style={{ position: 'relative' }}>
                                <User size={18} color={C.textMuted} style={{
                                    position: 'absolute', left: 12, top: '50%',
                                    transform: 'translateY(-50%)', pointerEvents: 'none',
                                }} />
                                <input
                                    type="text"
                                    value={name}
                                    onChange={e => setName(e.target.value)}
                                    onKeyDown={e => e.key === 'Enter' && handleLanjut()}
                                    placeholder="Masukkan nama lengkap"
                                    maxLength={100}
                                    className="w9id-input"
                                    style={{
                                        width: '100%', height: 48, boxSizing: 'border-box',
                                        border: `1px solid ${nameError ? '#DC3545' : C.border}`,
                                        borderRadius: 8, paddingLeft: 40, paddingRight: 12,
                                        fontSize: 13, color: C.textPrimary,
                                        background: C.bg, fontFamily: F,
                                    }}
                                />
                            </div>
                            {nameError && (
                                <p style={{ color: '#DC3545', fontSize: 12, margin: '5px 0 0', fontFamily: F }}>
                                    {nameError}
                                </p>
                            )}
                        </div>

                        {/* ── Nomor Telepon ── */}
                        <div>
                            <label style={{
                                display: 'block', fontSize: 13, fontWeight: 700,
                                color: C.textPrimary, marginBottom: 8, fontFamily: F,
                            }}>
                                Nomor Telepon
                            </label>
                            <div style={{ position: 'relative' }}>
                                <Phone size={18} color={C.textMuted} style={{
                                    position: 'absolute', left: 12, top: '50%',
                                    transform: 'translateY(-50%)', pointerEvents: 'none',
                                }} />
                                <input
                                    type="tel"
                                    value={phone}
                                    onChange={e => setPhone(e.target.value.replace(/[^0-9]/g, ''))}
                                    onKeyDown={e => e.key === 'Enter' && handleLanjut()}
                                    placeholder="0812 3456 7890"
                                    maxLength={15}
                                    className="w9id-input"
                                    style={{
                                        width: '100%', height: 48, boxSizing: 'border-box',
                                        border: `1px solid ${phoneError ? '#DC3545' : C.border}`,
                                        borderRadius: 8, paddingLeft: 40, paddingRight: 12,
                                        fontSize: 13, color: C.textPrimary,
                                        background: C.bg, fontFamily: F,
                                    }}
                                />
                            </div>
                            {phoneError && (
                                <p style={{ color: '#DC3545', fontSize: 12, margin: '5px 0 0', fontFamily: F }}>
                                    {phoneError}
                                </p>
                            )}
                        </div>

                        {/* ── Mahasiswa checkbox ── */}
                        <div
                            onClick={() => setIsMahasiswa(p => !p)}
                            style={{ display: 'flex', alignItems: 'flex-start', gap: 12, cursor: 'pointer' }}
                        >
                            <div style={{
                                width: 20, height: 20, borderRadius: 4, flexShrink: 0, marginTop: 1,
                                border: `1.5px solid ${isMahasiswa ? C.accent : C.border}`,
                                background: isMahasiswa ? C.accent : C.surface,
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                transition: 'all 0.15s',
                            }}>
                                {isMahasiswa && <Check size={12} color="#FFFFFF" strokeWidth={2.5} />}
                            </div>
                            <div>
                                <p style={{ fontSize: 13, fontWeight: 600, color: C.textPrimary, margin: 0, fontFamily: F }}>
                                    Saya adalah mahasiswa STIE Totalwin Semarang
                                </p>
                                <p style={{ fontSize: 11, color: C.textMuted, margin: '2px 0 0', fontFamily: F }}>
                                    Opsional
                                </p>
                            </div>
                        </div>

                        {/* ── CTA Button ── */}
                        <div style={{ paddingTop: 4 }}>
                            <button
                                onClick={handleLanjut}
                                className="w9id-btn"
                                style={{
                                    width: '100%', height: 52,
                                    background: C.accent, color: '#FFFFFF',
                                    border: 'none', borderRadius: 8,
                                    fontSize: 15, fontWeight: 700, cursor: 'pointer',
                                    fontFamily: F, transition: 'background 0.15s',
                                    boxShadow: '0 4px 6px rgba(0,0,0,0.1)',
                                }}
                            >
                                Masuk
                            </button>
                        </div>
                    </section>
                </main>

            </div>
        </PelangganLayout>
    );
}
