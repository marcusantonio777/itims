import React, { useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import './dashboard.css';

type MetricKey =
    | 'totalAssets'
    | 'availableAssets'
    | 'deployedAssets'
    | 'repairAssets'
    | 'retiredAssets'
    | 'serviceAlerts'
    | 'warrantyAlerts'
    | 'lowStockItems'
    | 'renewalsDue'
    | 'updatesDue'
    | 'activeLicenses'
    | 'roomDeployments';

interface DashboardAlert {
    title: string;
    detail: string;
    date?: string;
    status?: string;
    quantity?: number;
    minimum?: number;
    renewalDate?: string | null;
    updateDate?: string | null;
}

interface DashboardData {
    staffName: string;
    generatedAt: string;
    metrics: Record<MetricKey, number>;
    categories: Array<{ name: string; count: number }>;
    statuses: Array<{ name: string; count: number }>;
    alerts: {
        service: DashboardAlert[];
        warranty: DashboardAlert[];
        stock: DashboardAlert[];
        licenses: DashboardAlert[];
    };
}

const numberFormat = new Intl.NumberFormat();
const shortDateFormat = new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
const statusColors: Record<string, string> = {
    Available: '#3b82f6',
    Deployed: '#10b981',
    Repair: '#f59e0b',
    Retired: '#94a3b8',
};

function Icon({ name, size = 20 }: { name: string; size?: number }) {
    const paths: Record<string, React.ReactNode> = {
        assets: <><path d="m12 3 9 4.5v9L12 21l-9-4.5v-9L12 3Z" /><path d="m3.5 7.5 8.5 4.3 8.5-4.3M12 12v9" /></>,
        deployed: <><path d="m5 12 4 4L19 6" /><circle cx="12" cy="12" r="9" /></>,
        service: <><path d="M12 8v4l3 2" /><circle cx="12" cy="12" r="9" /></>,
        stock: <><path d="M4 7h16v13H4zM8 7V4h8v3M8 12h8M8 16h5" /></>,
        warranty: <><path d="M12 3 5 6v5c0 4.5 2.8 7.8 7 10 4.2-2.2 7-5.5 7-10V6l-7-3Z" /><path d="m9 12 2 2 4-4" /></>,
        license: <><rect x="4" y="3" width="16" height="18" rx="2" /><path d="M8 8h8M8 12h5M8 16h8" /></>,
        refresh: <><path d="M20 7v5h-5M4 17v-5h5" /><path d="M5.6 9a7 7 0 0 1 11.6-2L20 12M4 12l2.8 5a7 7 0 0 0 11.6-2" /></>,
        arrow: <><path d="M7 17 17 7M7 7h10v10" /></>,
    };
    return (
        <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            {paths[name] ?? paths.assets}
        </svg>
    );
}

function formatDate(value?: string | null): string {
    if (!value) return 'Not scheduled';
    const parsed = new Date(`${value.slice(0, 10)}T00:00:00`);
    return Number.isNaN(parsed.getTime()) ? 'Date unavailable' : shortDateFormat.format(parsed);
}

function greeting(date: Date): string {
    const hour = date.getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
}

function MetricCard({
    label,
    value,
    hint,
    icon,
    tone,
    href,
}: {
    label: string;
    value: number;
    hint: string;
    icon: string;
    tone: string;
    href: string;
}) {
    return (
        <a className="dash-metric" href={href}>
            <div className={`dash-metric-icon ${tone}`}><Icon name={icon} size={19} /></div>
            <div className="dash-metric-copy">
                <span>{label}</span>
                <strong>{numberFormat.format(value)}</strong>
                <small>{hint}</small>
            </div>
            <span className="dash-metric-link" aria-hidden="true"><Icon name="arrow" size={16} /></span>
        </a>
    );
}

function CategoryChart({ categories }: { categories: DashboardData['categories'] }) {
    const [showAll, setShowAll] = useState(false);
    const visible = showAll ? categories : categories.slice(0, 7);
    const maxCount = Math.max(1, ...visible.map((item) => item.count));
    if (categories.length === 0) {
        return <div className="dash-empty">Add asset categories and inventory to see the distribution.</div>;
    }
    return (
        <div className="dash-category-chart">
            {visible.map((item, index) => (
                <div className="dash-bar-row" key={item.name}>
                    <span className="dash-bar-label" title={item.name}>{item.name}</span>
                    <div className="dash-bar-track">
                        <div className="dash-bar-fill" style={{
                            width: `${Math.max(item.count > 0 ? 3 : 0, item.count / maxCount * 100)}%`,
                            opacity: 1 - index * 0.045,
                        }} />
                    </div>
                    <span className="dash-bar-value">{numberFormat.format(item.count)}</span>
                </div>
            ))}
            {categories.length > 7 && (
                <button className="dash-text-button" type="button" onClick={() => setShowAll(!showAll)}>
                    {showAll ? 'Show fewer' : `View all ${categories.length} categories`}
                </button>
            )}
        </div>
    );
}

function StatusChart({ statuses, total }: { statuses: DashboardData['statuses']; total: number }) {
    const segments = useMemo(() => {
        let offset = 0;
        const circumference = 2 * Math.PI * 43;
        return statuses.map((status) => {
            const length = total > 0 ? (status.count / total) * circumference : 0;
            const segment = { ...status, length, offset, color: statusColors[status.name] ?? '#64748b' };
            offset += length;
            return segment;
        });
    }, [statuses, total]);
    return (
        <div className="dash-status-wrap">
            <div className="dash-donut">
                <svg viewBox="0 0 112 112" role="img" aria-label={`Asset status distribution across ${numberFormat.format(total)} assets`}>
                    <circle cx="56" cy="56" r="43" fill="none" stroke="#eef2f7" strokeWidth="12" />
                    <g transform="rotate(-90 56 56)">
                        {segments.map((segment) => (
                            <circle key={segment.name} cx="56" cy="56" r="43" fill="none"
                                stroke={segment.color} strokeWidth="12"
                                strokeDasharray={`${segment.length} ${2 * Math.PI * 43 - segment.length}`}
                                strokeDashoffset={-segment.offset} />
                        ))}
                    </g>
                </svg>
                <div className="dash-donut-center">
                    <strong>{numberFormat.format(total)}</strong>
                    <span>assets</span>
                </div>
            </div>
            <div className="dash-legend">
                {segments.map((segment) => (
                    <div className="dash-legend-item" key={segment.name}>
                        <span className="dash-legend-dot" style={{ backgroundColor: segment.color }} />
                        <span>{segment.name}</span>
                        <strong>{numberFormat.format(segment.count)}</strong>
                    </div>
                ))}
                {statuses.length === 0 && <p className="dash-empty">No asset statuses to show yet.</p>}
            </div>
        </div>
    );
}

const alertTabs = [
    { key: 'service', label: 'Service', metric: 'serviceAlerts' as const, href: 'maintenance.php' },
    { key: 'warranty', label: 'Warranty', metric: 'warrantyAlerts' as const, href: 'reports.php?type=warranty' },
    { key: 'stock', label: 'Low stock', metric: 'lowStockItems' as const, href: 'supplies.php' },
    { key: 'licenses', label: 'Licenses', metric: 'renewalsDue' as const, href: 'software_licenses.php' },
];

function AlertPanel({ data }: { data: DashboardData }) {
    const [activeTab, setActiveTab] = useState('service');
    const tab = alertTabs.find((item) => item.key === activeTab) ?? alertTabs[0];
    const records = data.alerts[tab.key as keyof DashboardData['alerts']];
    return (
        <section className="dash-panel dash-alert-panel">
            <div className="dash-panel-heading">
                <div>
                    <p className="dash-eyebrow">Needs attention</p>
                    <h2>Operations watchlist</h2>
                </div>
                <a className="dash-outline-link" href={tab.href}>Open module <Icon name="arrow" size={14} /></a>
            </div>
            <div className="dash-tabs" role="group" aria-label="Filter dashboard alerts">
                {alertTabs.map((item) => (
                    <button type="button" aria-pressed={activeTab === item.key}
                        className={activeTab === item.key ? 'is-active' : ''}
                        key={item.key} onClick={() => setActiveTab(item.key)}>
                        {item.label}
                        <span>{numberFormat.format(data.metrics[item.metric])}</span>
                    </button>
                ))}
            </div>
            <div className="dash-alert-list">
                {records.map((record) => (
                    <div className="dash-alert-row" key={`${tab.key}-${record.title}`}>
                        <span className={`dash-alert-icon ${tab.key}`}><Icon name={
                            tab.key === 'stock' ? 'stock' : tab.key === 'licenses' ? 'license' : tab.key
                        } size={17} /></span>
                        <div className="dash-alert-main">
                            <strong>{record.title}</strong>
                            <span>{record.detail || 'Details not provided'}</span>
                        </div>
                        <div className="dash-alert-meta">
                            {tab.key === 'stock' ? (
                                <><strong className={(record.quantity ?? 0) <= 0 ? 'is-critical' : ''}>{record.quantity} on hand</strong><span>Min. {record.minimum}</span></>
                            ) : tab.key === 'licenses' ? (
                                <>
                                    <strong>{record.renewalDate ? `Renewal ${formatDate(record.renewalDate)}` : `Update ${formatDate(record.updateDate)}`}</strong>
                                    <span>{record.detail}</span>
                                </>
                            ) : (
                                <><strong>{formatDate(record.date)}</strong><span>{tab.key === 'service' ? record.status : 'Warranty expiry'}</span></>
                            )}
                        </div>
                    </div>
                ))}
                {records.length === 0 && (
                    <div className="dash-clear-state">
                        <span><Icon name="deployed" size={20} /></span>
                        <strong>All clear</strong>
                        <p>No {tab.label.toLowerCase()} alerts to show right now.</p>
                    </div>
                )}
            </div>
        </section>
    );
}

function Dashboard({ data }: { data: DashboardData }) {
    const generatedDate = new Date(data.generatedAt);
    const utilization = data.metrics.totalAssets > 0
        ? Math.round(data.metrics.deployedAssets / data.metrics.totalAssets * 100)
        : 0;
    const formattedToday = new Intl.DateTimeFormat(undefined, {
        weekday: 'long', month: 'long', day: 'numeric',
    }).format(generatedDate);
    const updatedTime = new Intl.DateTimeFormat(undefined, {
        hour: 'numeric', minute: '2-digit',
    }).format(generatedDate);

    return (
        <div className="dash-app">
            <section className="dash-hero">
                <div className="dash-hero-content">
                    <span className="dash-hero-label"><span /> LIVE OPERATIONS OVERVIEW</span>
                    <h1>{greeting(generatedDate)}, {data.staffName}.</h1>
                    <p>Your IT estate at a glance. Here is what needs your attention today.</p>
                    <div className="dash-hero-meta">
                        <span>{formattedToday}</span><i />
                        <span>Updated {updatedTime}</span>
                    </div>
                </div>
                <div className="dash-hero-actions">
                    <a className="dash-primary-action" href="inventory.php">View inventory <Icon name="arrow" size={16} /></a>
                    <a className="dash-secondary-action" href="issuance.php">New issuance <span>+</span></a>
                    <button className="dash-refresh-action" type="button" onClick={() => window.location.reload()} aria-label="Refresh dashboard">
                        <Icon name="refresh" size={16} /> Refresh data
                    </button>
                </div>
                <div className="dash-hero-orbit dash-orbit-one" />
                <div className="dash-hero-orbit dash-orbit-two" />
            </section>

            <div className="dash-section-heading">
                <div><p className="dash-eyebrow">ASSET HEALTH</p><h2>Inventory snapshot</h2></div>
                <a href="reports.php" className="dash-muted-link">View reports <Icon name="arrow" size={14} /></a>
            </div>

            <section className="dash-metrics-grid" aria-label="Inventory metrics">
                <MetricCard label="Total assets" value={data.metrics.totalAssets} hint="Tracked in inventory" icon="assets" tone="blue" href="inventory.php" />
                <MetricCard label="Deployed" value={data.metrics.deployedAssets} hint={`${utilization}% of all assets`} icon="deployed" tone="green" href="inventory.php" />
                <MetricCard label="Service due" value={data.metrics.serviceAlerts} hint="Overdue or due within 7 days" icon="service" tone="amber" href="maintenance.php" />
                <MetricCard label="Low stock" value={data.metrics.lowStockItems} hint="At or below minimum" icon="stock" tone="violet" href="supplies.php" />
            </section>

            <section className="dash-secondary-metrics" aria-label="Additional operational metrics">
                <div><span className="dash-secondary-icon warranty"><Icon name="warranty" size={16} /></span><span>Warranty due in 30 days</span><strong>{numberFormat.format(data.metrics.warrantyAlerts)}</strong></div>
                <div><span className="dash-secondary-icon license"><Icon name="license" size={16} /></span><span>License renewals due</span><strong>{numberFormat.format(data.metrics.renewalsDue)}</strong></div>
                <div><span className="dash-secondary-icon room"><Icon name="assets" size={16} /></span><span>Room deployments · 30 days</span><strong>{numberFormat.format(data.metrics.roomDeployments)}</strong></div>
            </section>

            <section className="dash-analytics-grid">
                <div className="dash-panel dash-category-panel">
                    <div className="dash-panel-heading">
                        <div><p className="dash-eyebrow">INVENTORY MIX</p><h2>Assets by category</h2></div>
                        <span className="dash-panel-caption">{data.categories.length} categories</span>
                    </div>
                    <CategoryChart categories={data.categories} />
                </div>
                <div className="dash-panel dash-status-panel">
                    <div className="dash-panel-heading">
                        <div><p className="dash-eyebrow">CURRENT STATE</p><h2>Asset status</h2></div>
                        <a href="inventory.php" className="dash-icon-link" aria-label="Open inventory"><Icon name="arrow" size={16} /></a>
                    </div>
                    <StatusChart statuses={data.statuses} total={data.metrics.totalAssets} />
                    <div className="dash-utilization">
                        <div className="dash-utilization-copy"><span>Deployment ratio</span><strong>{utilization}%</strong></div>
                        <div className="dash-progress"><span style={{ width: `${utilization}%` }} /></div>
                        <p>{numberFormat.format(data.metrics.availableAssets)} assets currently available</p>
                    </div>
                </div>
            </section>

            <section className="dash-bottom-grid">
                <AlertPanel data={data} />
                <aside className="dash-panel dash-action-panel">
                    <div className="dash-panel-heading">
                        <div><p className="dash-eyebrow">WORKSPACE</p><h2>At a glance</h2></div>
                    </div>
                    <div className="dash-glance-card">
                        <span className="dash-glance-icon repair"><Icon name="service" size={18} /></span>
                        <div><strong>{numberFormat.format(data.metrics.repairAssets)} assets</strong><span>Currently in repair</span></div>
                        <a href="maintenance.php" aria-label="View maintenance"><Icon name="arrow" size={15} /></a>
                    </div>
                    <div className="dash-glance-card">
                        <span className="dash-glance-icon license"><Icon name="license" size={18} /></span>
                        <div><strong>{numberFormat.format(data.metrics.activeLicenses)} licenses</strong><span>{numberFormat.format(data.metrics.updatesDue)} updates due soon</span></div>
                        <a href="software_licenses.php" aria-label="View software licenses"><Icon name="arrow" size={15} /></a>
                    </div>
                    <div className="dash-glance-card">
                        <span className="dash-glance-icon retired"><Icon name="assets" size={18} /></span>
                        <div><strong>{numberFormat.format(data.metrics.retiredAssets)} assets</strong><span>Retired from service</span></div>
                        <a href="inventory.php" aria-label="View inventory"><Icon name="arrow" size={15} /></a>
                    </div>
                    <a className="dash-full-report" href="reports.php">Explore all reports <Icon name="arrow" size={15} /></a>
                </aside>
            </section>
            <p className="dash-footnote">Figures are based on records available in this installation at the time shown above.</p>
        </div>
    );
}

const rootElement = document.getElementById('dashboard-root');
if (rootElement?.dataset.dashboard) {
    const data: DashboardData = JSON.parse(rootElement.dataset.dashboard);
    createRoot(rootElement).render(<Dashboard data={data} />);
}
