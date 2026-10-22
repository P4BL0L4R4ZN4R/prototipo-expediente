import { AppContent } from '@/components/default/app-content';
import { AppShell } from '@/components/default/app-shell';
import { AppSidebar } from '@/components/default/app-sidebar';
import { AppSidebarHeader } from '@/components/default/app-sidebar-header';
import { type BreadcrumbItem } from '@/types';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: { children: React.ReactNode; breadcrumbs?: BreadcrumbItem[] }) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
