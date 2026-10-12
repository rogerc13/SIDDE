import { $, moment } from "./globals.js";
export function refresh(){
    // The hero is fully re-rendered by renderReportHero() in each renderer.
    $(".course-amount-number").html("");
    $('.row-graphs').html('');
    $(".table-col-helper").html("");
}

export function formatServerDate(dateStr){
    if (!dateStr && dateStr !== 0) return '';
    try {
        if (typeof moment === 'function') {
            // Try strict parse for common formats; fall back to loose parse.
            let m = moment(dateStr, ['YYYY-MM-DD','YYYY-M-DD','YYYY-MM-D','YYYY-M-D','DD-MM-YYYY','D-M-YYYY'], true);
            if (!m.isValid()) {
                m = moment(dateStr);
            }
            return m.isValid() ? m.format('DD-MM-YYYY') : String(dateStr);
        }
    } catch (e) {
        // fall through to naive fallback
    }

    // Naive fallback: handle 'YYYY-MM-DD' -> 'DD-MM-YYYY'
    const ymd = /^(\d{4})-(\d{2})-(\d{2})$/;
    const dmy = /^(\d{2})-(\d{2})-(\d{4})$/;
    let m = String(dateStr).match(ymd);
    if (m) return `${m[3]}-${m[2]}-${m[1]}`;
    m = String(dateStr).match(dmy);
    if (m) return String(dateStr);
    // Try Date parse
    const dt = new Date(dateStr);
    if (!Number.isNaN(dt.getTime())){
        const dd = String(dt.getDate()).padStart(2,'0');
        const mm = String(dt.getMonth()+1).padStart(2,'0');
        const yy = dt.getFullYear();
        return `${dd}-${mm}-${yy}`;
    }
    return String(dateStr);
}
