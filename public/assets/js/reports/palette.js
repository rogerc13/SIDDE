// Single source of truth for report colors.
//
// Defaults mirror App\Services\Reports\ChartPalette so server-rendered PDF
// charts and on-screen charts match. loadTheme() refreshes these values from
// GET /reports/options; on failure the defaults are kept.

const DEFAULT_THEME = {
    categorical: [
        '#4C72B0', '#DD8452', '#55A868', '#C44E52', '#8172B3',
        '#937860', '#DA8BC3', '#8C8C8C', '#CCB974', '#64B5CD',
    ],
    courseStatus: {
        'Por Dictar': '#8172B3',
        'En Curso': '#4C72B0',
        'Culminado': '#55A868',
        'Cancelado': '#C44E52',
    },
    participantStatus: {
        'En Curso': '#4C72B0',
        'Reprobado': '#DD8452',
        'Aprobado': '#55A868',
        'Cancelado': '#C44E52',
        'Por Iniciar': '#8172B3',
    },
    gender: {
        'Masculino': '#4C72B0',
        'Femenino': '#DD8452',
        'No especificado': '#8C8C8C',
    },
};

const NEUTRAL = '#8C8C8C';
const OTHERS = '#999999';

export const theme = JSON.parse(JSON.stringify(DEFAULT_THEME));

function normalize(value) {
    return String(value ?? '')
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

export function categoryColor(index) {
    const palette = theme.categorical;
    return palette[((index % palette.length) + palette.length) % palette.length];
}

export function othersColor() {
    return OTHERS;
}

export function courseStatusColor(name) {
    const key = normalize(name);
    const map = theme.courseStatus;

    if (key === 'por dictar' || key === 'por iniciar') return map['Por Dictar'] || NEUTRAL;
    if (key === 'en curso') return map['En Curso'] || NEUTRAL;
    if (key === 'culminado' || key === 'aprobado') return map['Culminado'] || NEUTRAL;
    if (key === 'cancelado') return map['Cancelado'] || NEUTRAL;

    return NEUTRAL;
}

export function participantStatusColor(name) {
    const key = normalize(name);
    const map = theme.participantStatus;

    if (key === 'en curso') return map['En Curso'] || NEUTRAL;
    if (key === 'reprobado' || key === 'suspendido') return map['Reprobado'] || NEUTRAL;
    if (key === 'aprobado') return map['Aprobado'] || NEUTRAL;
    if (key === 'cancelado') return map['Cancelado'] || NEUTRAL;
    if (key === 'por iniciar' || key === 'por dictar' || key === 'en espera de curso') {
        return map['Por Iniciar'] || NEUTRAL;
    }

    return NEUTRAL;
}

export function genderColor(label) {
    const key = normalize(label);
    const map = theme.gender;

    if (key === 'masculino') return map['Masculino'] || NEUTRAL;
    if (key === 'femenino') return map['Femenino'] || NEUTRAL;

    return map['No especificado'] || NEUTRAL;
}

export function hexToRgba(hex, alpha = 1) {
    const value = String(hex).replace('#', '');
    const full = value.length === 3
        ? value.split('').map((c) => c + c).join('')
        : value;

    const int = parseInt(full, 16);
    if (Number.isNaN(int)) {
        return hex;
    }

    const r = (int >> 16) & 255;
    const g = (int >> 8) & 255;
    const b = int & 255;

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

export async function loadTheme() {
    try {
        const response = await fetch('/reports/options', { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            return;
        }

        const data = await response.json();
        const palette = data.palette || {};

        if (Array.isArray(palette.categorical) && palette.categorical.length) {
            theme.categorical = palette.categorical;
        }
        if (palette.courseStatus) {
            Object.assign(theme.courseStatus, palette.courseStatus);
        }
        if (palette.participantStatus) {
            Object.assign(theme.participantStatus, palette.participantStatus);
        }
        if (palette.gender) {
            Object.assign(theme.gender, palette.gender);
        }
    } catch (_error) {
        // Keep the built-in defaults.
    }
}
