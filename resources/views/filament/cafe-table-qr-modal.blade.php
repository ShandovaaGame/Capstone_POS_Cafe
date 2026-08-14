<div style="text-align: center; padding: 16px 0;">
    <p style="margin-bottom: 16px; font-size: 14px; color: #6b7280;">
        URL: <a href="{{ $record->qr_code_url }}" target="_blank" style="color: #3b6fd4;">{{ $record->qr_code_url }}</a>
    </p>

    <img src="{{ $dataUri }}" width="260" height="260"
         style="border-radius: 8px; border: 1px solid #e5e7eb; padding: 8px;"
         alt="QR Meja {{ $record->table_number }}" />

    <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: center;">
        <a href="{{ $dataUri }}" download="qr-meja-{{ $record->table_number }}.svg"
           style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 20px;
                  background: #3b6fd4; color: #fff; border-radius: 8px; text-decoration: none;
                  font-size: 14px; font-weight: 500;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Download QR
        </a>
    </div>
</div>
