import LoginPage from '@/Components/Shared/LoginPage';
import { Utensils } from 'lucide-react';

export default function KitchenLogin() {
    return (
        <LoginPage
            action={route('dapur.login.attempt')}
            icon={Utensils}
            title="Dapur W9 Cafe"
            subtitle="Masuk ke Kitchen Display System"
            dataInterface="kitchen"
        />
    );
}
