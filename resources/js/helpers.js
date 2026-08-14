export const formatRupiah = (amount) => {
    if (amount == null || isNaN(amount)) return 'Rp0';
    return 'Rp' + Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
};

export const formatDate = (date) =>
    new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'Asia/Jakarta' }).format(new Date(date));

export const formatTime = (date) =>
    new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' }).format(new Date(date));

export const summarizeItems = (items) =>
    items.map(i => `${i.quantity}x ${i.menu.name}`).join(', ');
