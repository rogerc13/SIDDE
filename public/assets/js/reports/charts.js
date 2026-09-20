import { $ } from "./globals.js";
export function buildSeriesFor(label, xLabels, points, keyName){
    // points assumed like { x: '2025-10-01', y: 3, category: 'X' } or { x, y, status }
    const map = {};
    points.forEach(p => {
        if(p[keyName] === label){
            // Treat 0 (and negative) as no-data so it doesn't render
            map[p.x] = (typeof p.y === 'number' && p.y > 0) ? p.y : null;
        }
    });
    // return null for missing values so Chart.js will connect across gaps when spanGaps:true
    return xLabels.map(x => ({ x: x, y: (map[x] !== undefined ? map[x] : null) }));
}



export function shouldHideLinePoints(){
    // Hide point markers for daily granularity to keep the chart readable
    return $('#step').val() === '1 day';
}

export function shouldConnectLineGaps(){
    // For daily charts, connect across nulls so the line reads as a trend
    return $('#step').val() === '1 day';
}

export function applySmartLineMarkers(dataset){
    const points = Array.isArray(dataset.data) ? dataset.data : [];

    const nonNullIndexes = [];
    const values = [];
    points.forEach((p, idx) => {
        if (p && p.y !== null && typeof p.y === 'number') {
            nonNullIndexes.push(idx);
            values.push(p.y);
        }
    });

    if (nonNullIndexes.length === 0) {
        return {
            ...dataset,
            pointRadius: 0,
            pointHoverRadius: 0,
        };
    }

    const firstIdx = nonNullIndexes[0];
    const lastIdx = nonNullIndexes[nonNullIndexes.length - 1];
    const minVal = Math.min(...values);
    const maxVal = Math.max(...values);

    let minIdx = firstIdx;
    let maxIdx = firstIdx;
    for (const idx of nonNullIndexes) {
        const y = points[idx].y;
        if (y === minVal) {
            minIdx = idx;
            break;
        }
    }
    for (const idx of nonNullIndexes) {
        const y = points[idx].y;
        if (y === maxVal) {
            maxIdx = idx;
            break;
        }
    }

    const markerIndexes = new Set([firstIdx, lastIdx, minIdx, maxIdx]);

    return {
        ...dataset,
        pointRadius: (ctx) => (markerIndexes.has(ctx.dataIndex) ? 3 : 0),
        pointHoverRadius: (ctx) => (markerIndexes.has(ctx.dataIndex) ? 5 : 0),
        pointHitRadius: (ctx) => (markerIndexes.has(ctx.dataIndex) ? 8 : 0),
    };
}

export function getMaxYFromDatasets(datasets){
    let maxY = 0;
    (datasets || []).forEach(ds => {
        const points = Array.isArray(ds.data) ? ds.data : [];
        points.forEach(p => {
            if (p && typeof p.y === 'number' && p.y > maxY) {
                maxY = p.y;
            }
        });
    });
    return maxY;
}

