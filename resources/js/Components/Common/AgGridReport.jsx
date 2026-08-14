import { useRef, useCallback, useMemo, useEffect, useState } from 'react';
import { AgGridReact } from 'ag-grid-react';
import { AllCommunityModule, ModuleRegistry } from 'ag-grid-community';
import * as XLSX from 'xlsx';
import { formatRupiah, formatDate } from '@/helpers';
import { FileSpreadsheet, FileText } from 'lucide-react';

// Register all Community modules once (required for AG Grid v35+)
ModuleRegistry.registerModules([AllCommunityModule]);

export const REPORT_COLUMN_DEFS = [
    {
        field: 'category',
        headerName: 'Akun',
        flex: 1,
        pinned: 'left',
        filter: 'agTextColumnFilter',
        cellRenderer: (params) => {
            const indent = params.data?.indent_level ?? 0;
            const isBold = params.data?.is_bold ?? false;
            const type = params.data?.type ?? '';
            const style = {
                paddingLeft: `${12 + indent * 20}px`,
                fontWeight: isBold || type === 'Section' || type === 'GrandTotal' ? 700 : type === 'Total' ? 600 : 400,
            };
            return <span style={style}>{params.value}</span>;
        },
    },
    {
        field: 'date',
        headerName: 'Tanggal',
        width: 130,
        valueFormatter: (params) => (params.value ? formatDate(params.value) : '-'),
        filter: 'agTextColumnFilter',
    },
    {
        field: 'type',
        headerName: 'Tipe',
        width: 130,
        filter: 'agTextColumnFilter',
    },
    {
        field: 'amount',
        headerName: 'Jumlah',
        width: 180,
        valueFormatter: (params) => formatRupiah(params.value),
        cellClass: 'ag-grid-currency-cell',
        filter: 'agNumberColumnFilter',
        type: 'rightAligned',
    },
    {
        field: 'running_total',
        headerName: 'Saldo Berjalan',
        width: 200,
        valueFormatter: (params) => (params.value != null ? formatRupiah(params.value) : '-'),
        cellClass: 'ag-grid-currency-cell',
        filter: 'agNumberColumnFilter',
        type: 'rightAligned',
    },
];

const DEFAULT_COL_DEF = {
    sortable: true,
    filter: true,
    resizable: true,
    floatingFilter: false,
    minWidth: 80,
};

const ROW_CLASS_RULES = {
    'ag-grid-row-section': (params) => params.data?.type === 'Section',
    'ag-grid-row-total': (params) => params.data?.type === 'Total',
    'ag-grid-row-grand-total': (params) => params.data?.type === 'GrandTotal',
    'ag-grid-row-bold': (params) => params.data?.is_bold === true,
};

export default function AgGridReport({
    rowData = [],
    columnDefs = REPORT_COLUMN_DEFS,
    defaultColDef = DEFAULT_COL_DEF,
    gridHeight = 520,
    fileName = 'report',
}) {
    const gridRef = useRef();
    const [isClient, setIsClient] = useState(false);

    useEffect(() => {
        setIsClient(true);
    }, []);

    const themeClass = 'ag-theme-quartz';

    const mergedDefaultColDef = useMemo(
        () => ({ ...DEFAULT_COL_DEF, ...defaultColDef }),
        [defaultColDef],
    );

    const handleExportCsv = useCallback(() => {
        gridRef.current?.api.exportDataAsCsv({
            fileName: `${fileName}-${new Date().toISOString().split('T')[0]}.csv`,
            columnKeys: columnDefs.map((c) => c.field).filter(Boolean),
        });
    }, [fileName, columnDefs]);

    const handleExportExcel = useCallback(() => {
        const rows = [];
        gridRef.current?.api.forEachNode((node) => {
            const row = {};
            columnDefs.forEach((col) => {
                if (!col.field) return;
                const value = node.data[col.field];
                if (col.cellClass === 'ag-grid-currency-cell' && value != null) {
                    row[col.headerName || col.field] = formatRupiah(value);
                } else if (col.field === 'date' && value) {
                    row[col.headerName || col.field] = formatDate(value);
                } else {
                    row[col.headerName || col.field] = value ?? '';
                }
            });
            rows.push(row);
        });

        const ws = XLSX.utils.json_to_sheet(rows);

        const colWidths = columnDefs.map((col) => {
            const headerLen = (col.headerName || col.field || '').length;
            const maxDataLen = rows.reduce((max, row) => {
                const val = row[col.headerName || col.field] || '';
                return Math.max(max, String(val).length);
            }, headerLen);
            return { wch: Math.min(Math.max(maxDataLen + 2, headerLen + 2), 50) };
        });
        ws['!cols'] = colWidths;

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Report');
        XLSX.writeFile(wb, `${fileName}-${new Date().toISOString().split('T')[0]}.xlsx`);
    }, [fileName, columnDefs]);

    const onGridReady = useCallback((params) => {
        params.api.autoSizeAllColumns();
        setTimeout(() => {
            params.api.autoSizeAllColumns();
        }, 200);
    }, []);

    const getRowHeight = useCallback((params) => {
        if (params.data?.type === 'Section') return 40;
        return 36;
    }, []);

    if (!isClient) {
        return (
            <div className="ag-grid-placeholder" style={{ height: gridHeight }}>
                <div className="flex items-center justify-center h-full text-sm text-gray-400">
                    Memuat tabel...
                </div>
            </div>
        );
    }

    return (
        <div className="ag-grid-report-wrapper space-y-3">
            <div className="ag-grid-toolbar flex items-center gap-2">
                <button
                    type="button"
                    onClick={handleExportCsv}
                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer"
                >
                    <FileText size={14} />
                    Ekspor CSV
                </button>
                <button
                    type="button"
                    onClick={handleExportExcel}
                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer"
                >
                    <FileSpreadsheet size={14} />
                    Ekspor Excel
                </button>
                <span className="text-xs text-gray-400 ml-auto">
                    {rowData.length} baris
                </span>
            </div>

            <div
                className={`${themeClass} ag-grid-report-grid`}
                style={{ height: gridHeight, width: '100%' }}
            >
                <AgGridReact
                    ref={gridRef}
                    rowData={rowData}
                    columnDefs={columnDefs}
                    defaultColDef={mergedDefaultColDef}
                    rowClassRules={ROW_CLASS_RULES}
                    getRowHeight={getRowHeight}
                    animateRows
                    enableCellTextSelection
                    ensureDomOrder
                    suppressMovableColumns={false}
                    rowSelection={{ mode: 'multiRow' }}
                    onGridReady={onGridReady}
                    domLayout="normal"
                    theme="legacy"
                />
            </div>
        </div>
    );
}
