import { createRoot } from 'react-dom/client';
import AgGridReport from '@/Components/Common/AgGridReport';

import 'ag-grid-community/styles/ag-grid.css';
import 'ag-grid-community/styles/ag-theme-quartz.css';

const container = document.getElementById('ag-grid-report-container');
if (container) {
    const rawProps = container.dataset.reportProps;
    if (rawProps) {
        try {
            const props = JSON.parse(rawProps);
            const { rows, ...rest } = props;
            const root = createRoot(container);
            root.render(<AgGridReport rowData={rows} {...rest} />);
        } catch (e) {
            const root = createRoot(container);
            root.render(<div className="p-4 text-red-500 text-sm">Failed to load report grid.</div>);
        }
    }
}
