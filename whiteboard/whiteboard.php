<!-- whiteboard.php -->
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>無限白板</title>
    <style>
        html, body {
            margin: 0;
            height: 100%;
            overflow: hidden;
            background-color: #121212;
        }
        canvas {
            display: block;
            background-color: #121212;
            cursor: crosshair;
        }
    </style>
</head>
<body>
<canvas id="whiteboard" style="background: #222; display:block;"></canvas>
<div style="position: fixed; top: 10px; left: 10px; z-index: 1000;">
    <button onclick="setTool('pen')">✏️ Pen</button>
    <button onclick="setTool('eraser')">🧽 Eraser (Object)</button>
</div>

<script>
    const canvas = document.getElementById('whiteboard');
    const ctx = canvas.getContext('2d');

    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    // 畫筆設定
    let currentTool = 'pen';
    let currentColor = '#ffffff';
    let currentLineWidth = 2;
    let isDrawing = false;
    let lastPos = { x: 0, y: 0 };

    // 畫布偏移與縮放
    let offset = { x: 0, y: 0 };
    let scale = 1;
    let isDraggingCanvas = false;
    let dragStart = { x: 0, y: 0 };

    // 儲存所有筆跡，每條筆跡是 [{x,y,color,width}]
    let paths = [];

    // 是否需要重繪
    let needsRedraw = true;

    // 工具切換
    function setTool(tool) {
        currentTool = tool;
    }

    // 取得滑鼠相對座標
    function toCanvasCoords(x, y) {
        return {
            x: (x - offset.x) / scale,
            y: (y - offset.y) / scale
        };
    }

    function distance(p1, p2) {
        const dx = p1.x - p2.x;
        const dy = p1.y - p2.y;
        return Math.sqrt(dx * dx + dy * dy);
    }

    // 事件處理
    canvas.addEventListener('mousedown', (e) => {
        const pos = toCanvasCoords(e.clientX, e.clientY);

        if (e.button === 2) {
            isDraggingCanvas = true;
            dragStart = { x: e.clientX, y: e.clientY };
            return;
        }

        if (currentTool === 'eraser') {
            // 檢查是否有筆跡在附近，刪除整筆
            const threshold = 10 / scale;
            for (let i = 0; i < paths.length; i++) {
                const path = paths[i];
                if (path.some(p => distance(p, pos) < threshold)) {
                    paths.splice(i, 1);
                    needsRedraw = true;
                    return;
                }
            }
        } else {
            isDrawing = true;
            lastPos = pos;
            paths.push([{ x: pos.x, y: pos.y, color: currentColor, width: currentLineWidth }]);
            needsRedraw = true;
        }
    });

    canvas.addEventListener('mousemove', (e) => {
        const pos = toCanvasCoords(e.clientX, e.clientY);

        if (isDraggingCanvas) {
            const dx = e.clientX - dragStart.x;
            const dy = e.clientY - dragStart.y;
            offset.x += dx;
            offset.y += dy;
            dragStart = { x: e.clientX, y: e.clientY };
            needsRedraw = true;
        } else if (isDrawing && currentTool === 'pen') {
            const currentPath = paths[paths.length - 1];
            currentPath.push({ x: pos.x, y: pos.y, color: currentColor, width: currentLineWidth });
            lastPos = pos;
            needsRedraw = true;
        }
    });

    canvas.addEventListener('mouseup', () => {
        isDrawing = false;
        isDraggingCanvas = false;
    });

    canvas.addEventListener('mouseleave', () => {
        isDrawing = false;
        isDraggingCanvas = false;
    });

    // 滾輪縮放
    canvas.addEventListener('wheel', (e) => {
        if (e.ctrlKey) {
            e.preventDefault();
            const zoomIntensity = 0.1;
            const oldScale = scale;

            if (e.deltaY < 0) {
                scale = Math.min(5, scale * (1 + zoomIntensity));
            } else {
                scale = Math.max(0.2, scale * (1 - zoomIntensity));
            }

            const rect = canvas.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            offset.x = mouseX - (mouseX - offset.x) * (scale / oldScale);
            offset.y = mouseY - (mouseY - offset.y) * (scale / oldScale);

            needsRedraw = true;
        }
    }, { passive: false });

    // 右鍵禁用
    canvas.addEventListener('contextmenu', e => e.preventDefault());

    // 畫布縮放重繪
    window.addEventListener('resize', () => {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        needsRedraw = true;
    });

    // 畫出所有筆跡
    function draw() {
        if (!needsRedraw) {
            requestAnimationFrame(draw);
            return;
        }

        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.save();
        ctx.translate(offset.x, offset.y);
        ctx.scale(scale, scale);

        for (const path of paths) {
            if (path.length < 2) continue;

            ctx.beginPath();
            ctx.moveTo(path[0].x, path[0].y);
            for (let i = 1; i < path.length; i++) {
                ctx.strokeStyle = path[i].color;
                ctx.lineWidth = path[i].width;
                ctx.lineTo(path[i].x, path[i].y);
            }
            ctx.stroke();
        }

        ctx.restore();
        needsRedraw = false;
        requestAnimationFrame(draw);
    }

    requestAnimationFrame(draw);
</script>
</body>
</html>
