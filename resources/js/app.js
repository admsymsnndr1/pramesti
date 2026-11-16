import Alpine from "alpinejs";
import { Chart, registerables } from "chart.js";
import {
    createIcons,
    Activity, AlertCircle, ArrowLeft, BarChart3, Briefcase, Calendar, CalendarRange, CalendarX, CheckCircle2,
    CheckSquare, ChevronDown, ChevronRight, ChevronsUpDown, Clipboard, ClipboardCheck, ClipboardList, Clock,
    CornerDownRight, Download, Eye, EyeOff, FileSpreadsheet, FileText, Folder, GitBranch, Hash, Image, Inbox, Info,
    KanbanSquare, Layers, LayoutDashboard, LayoutGrid, Link, ListChecks, LogIn, LogOut, Menu, Moon, Package,
    PackageOpen, Paperclip, Pencil, Plus, Search, Shield, ShieldCheck, ShieldPlus, SquareCheckBig, Sun, Trash2,
    TrendingUp, User, UserCog, UserPlus, UserRound, Users, X,
} from "lucide";

// only the icons the views use, so the bundle stays small (add new ones here)
const icons = {
    Activity, AlertCircle, ArrowLeft, BarChart3, Briefcase, Calendar, CalendarRange, CalendarX, CheckCircle2,
    CheckSquare, ChevronDown, ChevronRight, ChevronsUpDown, Clipboard, ClipboardCheck, ClipboardList, Clock,
    CornerDownRight, Download, Eye, EyeOff, FileSpreadsheet, FileText, Folder, GitBranch, Hash, Image, Inbox, Info,
    KanbanSquare, Layers, LayoutDashboard, LayoutGrid, Link, ListChecks, LogIn, LogOut, Menu, Moon, Package,
    PackageOpen, Paperclip, Pencil, Plus, Search, Shield, ShieldCheck, ShieldPlus, SquareCheckBig, Sun, Trash2,
    TrendingUp, User, UserCog, UserPlus, UserRound, Users, X,
};

Chart.register(...registerables);
window.Chart = Chart;

// chart text & grid colours follow the light / dark theme
function applyChartTheme() {
    const dark = document.documentElement.classList.contains("dark");
    Chart.defaults.font.family = "Inter, ui-sans-serif, system-ui, sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = dark ? "#94a3b8" : "#64748b";
    Chart.defaults.borderColor = dark ? "rgba(148,163,184,.12)" : "rgba(100,116,139,.12)";
    Object.values(Chart.instances).forEach((chart) => {
        Object.values(chart.options.scales || {}).forEach((scale) => {
            if (scale.ticks) scale.ticks.color = Chart.defaults.color;
            if (scale.grid) scale.grid.color = Chart.defaults.borderColor;
        });
        if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = Chart.defaults.color;
        chart.update("none");
    });
}
applyChartTheme();
window.addEventListener("theme-changed", applyChartTheme);

/* ------------------------------------------------------------------
 | Icons: <i data-lucide="name"></i>
 * ------------------------------------------------------------------ */
window.refreshIcons = () => createIcons({ icons, attrs: { "stroke-width": 1.75 } });

/* ------------------------------------------------------------------
 | Toasts: toast.success("Saved"), toast.error("<ul>...</ul>")
 * ------------------------------------------------------------------ */
const toastStyles = {
    success: ["bg-emerald-600", "check-circle-2"],
    error: ["bg-rose-600", "alert-circle"],
    info: ["bg-slate-800", "info"],
};

function showToast(type, message, { duration = 3500 } = {}) {
    const [bg, icon] = toastStyles[type];
    let stack = document.getElementById("toast-stack");
    if (!stack) {
        stack = document.createElement("div");
        stack.id = "toast-stack";
        stack.className = "fixed right-4 top-4 z-50 flex w-80 flex-col gap-2";
        document.body.appendChild(stack);
    }

    const el = document.createElement("div");
    el.className = `${bg} flex items-start gap-3 rounded-lg px-4 py-3 text-sm text-white shadow-lg transition duration-300`;
    el.innerHTML = `<i data-lucide="${icon}" class="mt-0.5 h-4 w-4 shrink-0"></i><div class="flex-1"></div>`;
    el.querySelector("div").innerHTML = message;
    stack.appendChild(el);
    window.refreshIcons();

    setTimeout(() => {
        el.classList.add("opacity-0", "translate-x-4");
        setTimeout(() => el.remove(), 300);
    }, duration);
}

window.toast = {
    success: (message, options) => showToast("success", message, options),
    error: (message, options) => showToast("error", message, options),
    info: (message, options) => showToast("info", message, options),
};

/* ------------------------------------------------------------------
 | AJAX forms: <form data-ajax> posts as multipart, shows validation
 | errors in a toast and follows `redirect` from the JSON response.
 * ------------------------------------------------------------------ */
const escapeHtml = (text) =>
    String(text).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);

document.addEventListener("submit", async (event) => {
    const form = event.target.closest("form[data-ajax]");
    if (!form) return;
    event.preventDefault();

    const button = form.querySelector('[type="submit"]');
    if (button) button.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: "POST", // _method field handles PUT/DELETE
            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
            body: new FormData(form),
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const messages = data.errors ? Object.values(data.errors).flat() : [data.message || "Something went wrong, please try again."];
            toast.error(`<ul class="list-disc pl-4">${messages.map((m) => `<li>${escapeHtml(m)}</li>`).join("")}</ul>`, { duration: 6000 });
            if (button) button.disabled = false;
            return;
        }

        toast.success(escapeHtml(data.message || "Saved"));
        if (data.redirect) setTimeout(() => (window.location.href = data.redirect), 800);
        else if (button) button.disabled = false;
    } catch (error) {
        toast.error("Cannot reach the server.");
        if (button) button.disabled = false;
    }
});

/* ------------------------------------------------------------------
 | Confirm before destructive submits: <form data-confirm="Delete?">
 * ------------------------------------------------------------------ */
document.addEventListener(
    "submit",
    (event) => {
        const form = event.target.closest("form[data-confirm]");
        if (form && !window.confirm(form.dataset.confirm)) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    },
    true,
);

/* ------------------------------------------------------------------
 | Alpine components
 * ------------------------------------------------------------------ */
Alpine.data("theme", () => ({
    dark: document.documentElement.classList.contains("dark"),
    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle("dark", this.dark);
        try {
            localStorage.setItem("theme", this.dark ? "dark" : "light");
        } catch (e) {}
        window.dispatchEvent(new CustomEvent("theme-changed"));
    },
}));

// client-side filter for server-rendered tables: <tr data-search="...">
Alpine.data("tableFilter", () => ({
    query: "",
    visible: null,
    filter() {
        const q = this.query.trim().toLowerCase();
        let count = 0;
        this.$root.querySelectorAll("tbody tr[data-search]").forEach((row) => {
            const show = !q || row.dataset.search.toLowerCase().includes(q);
            row.classList.toggle("hidden", !show);
            if (show) count++;
        });
        this.visible = q ? count : null;
    },
}));

window.Alpine = Alpine;
Alpine.start();

window.refreshIcons();
