<?php

$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($clientIp, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('This temporary test page is available only from this computer.');
}

?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تجربة تخصيص علبة العطر</title>
    <style>
        :root { font-family: Tahoma, Arial, sans-serif; color: #24211f; background: #f4f1ec; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        header { padding: 24px max(20px, calc((100% - 1120px) / 2)); background: #29231f; color: #fff; }
        header h1 { margin: 0 0 7px; font-size: 25px; }
        header p { margin: 0; color: #ddd4c9; line-height: 1.7; }
        main { max-width: 1120px; margin: auto; padding: 22px; }
        .notice { padding: 12px 15px; border: 1px solid #dfc994; border-radius: 9px; background: #fff9e9; color: #665020; line-height: 1.7; }
        .layout { display: grid; grid-template-columns: minmax(270px, 350px) minmax(0, 1fr); gap: 18px; margin-top: 18px; align-items: start; }
        .panel { padding: 20px; border: 1px solid #e4ded6; border-radius: 12px; background: #fff; box-shadow: 0 5px 20px #3727190a; }
        h2 { margin: 0 0 16px; font-size: 19px; }
        .field { margin-bottom: 14px; }
        label { display: block; margin-bottom: 6px; font-weight: 700; font-size: 14px; }
        input, select { width: 100%; min-height: 41px; padding: 8px 10px; border: 1px solid #d4cec6; border-radius: 7px; background: white; color: #24211f; font: inherit; }
        input[type="color"] { height: 42px; padding: 4px; }
        input[type="file"] { font-size: 13px; }
        .hint { margin: 6px 0 0; color: #77716b; font-size: 12px; line-height: 1.6; }
        .buttons { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 18px; }
        button { min-height: 40px; padding: 9px 14px; border: 0; border-radius: 7px; background: #745d3f; color: #fff; font: inherit; cursor: pointer; }
        button.secondary { background: #eee9e2; color: #332c25; }
        button:hover { filter: brightness(1.07); }
        .preview-panel { text-align: center; }
        .preview-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; text-align: right; }
        .preview-head h2 { margin: 0; }
        .preview-head span { color: #77716b; font-size: 12px; }
        .canvas-wrap { width: min(100%, 600px); margin: 14px auto 0; overflow: hidden; border: 1px solid #eee9e2; border-radius: 10px; background: #f8f6f2; }
        canvas { display: block; width: 100%; height: auto; touch-action: none; cursor: grab; }
        canvas:active { cursor: grabbing; }
        .tip { margin: 12px 0 0; color: #77716b; font-size: 13px; }
        @media (max-width: 760px) { main { padding: 14px; } .layout { grid-template-columns: 1fr; } header { padding: 20px 16px; } .preview-head { align-items: flex-start; flex-direction: column; } }
    </style>
</head>
<body>
<header>
    <h1>تجربة تخصيص علبة العطر</h1>
    <p>غيّر الاسم واللون، أضف شعارك، واسحب العناصر على واجهة العلبة لتشوف شكل المنتج.</p>
</header>
<main>
    <div class="notice"><strong>نموذج أولي محلي.</strong> التخصيص هنا معاينة فقط؛ لا يتم حفظ الصورة أو ربطها بمنتج أو طلب.</div>
    <div class="layout">
        <section class="panel" aria-labelledby="controls-title">
            <h2 id="controls-title">تفاصيل التصميم</h2>
            <div class="field">
                <label for="packageType">شو بدك تصمّم؟</label>
                <select id="packageType">
                    <option value="carton">كرتونة عطر خارجية</option>
                    <option value="gift">علبة هدية عريضة</option>
                    <option value="bottle">عبوة الزجاجة مع الملصق</option>
                </select>
            </div>
            <div class="field"><label for="brand">اسم البراند</label><input id="brand" maxlength="32" value="MAISON AHMAD"></div>
            <div class="field"><label for="perfume">اسم العطر</label><input id="perfume" maxlength="32" value="Amber Oud"></div>
            <div class="field"><label for="tagline">عبارة أو وصف قصير</label><input id="tagline" maxlength="48" value="EAU DE PARFUM · 100 ML"></div>
            <div class="field"><label for="boxColor">لون العلبة</label><input id="boxColor" type="color" value="#d8c4a2"></div>
            <div class="field"><label for="inkColor">لون الكتابة</label><input id="inkColor" type="color" value="#392b20"></div>
            <div class="field">
                <label for="logoFile">شعار أو صورة صغيرة</label>
                <input id="logoFile" type="file" accept="image/png,image/jpeg,image/webp">
                <p class="hint">اختر صورة من جهازك. الأفضل شعار PNG بخلفية شفافة.</p>
            </div>
            <div class="buttons">
                <button id="downloadButton" type="button">تحميل المعاينة PNG</button>
                <button id="resetButton" class="secondary" type="button">إرجاع العناصر لمكانها</button>
            </div>
        </section>
        <section class="panel preview-panel" aria-labelledby="preview-title">
            <div class="preview-head"><h2 id="preview-title">معاينة العلبة</h2><span>اسحب البراند أو الاسم أو الشعار لتحريكهم</span></div>
            <div class="canvas-wrap"><canvas id="preview" width="720" height="820" aria-label="معاينة تصميم علبة عطر"></canvas></div>
            <p class="tip">هذه رسمة توضيحية ثنائية الأبعاد وليست قالب طباعة أو نموذج ثلاثي الأبعاد.</p>
        </section>
    </div>
</main>
<script>
    const canvas = document.getElementById('preview');
    const ctx = canvas.getContext('2d');
    const controls = {
        packageType: document.getElementById('packageType'),
        brand: document.getElementById('brand'),
        perfume: document.getElementById('perfume'),
        tagline: document.getElementById('tagline'),
        boxColor: document.getElementById('boxColor'),
        inkColor: document.getElementById('inkColor'),
        logoFile: document.getElementById('logoFile')
    };
    const originalPositions = {
        brand: { x: 360, y: 330 },
        perfume: { x: 360, y: 418 },
        tagline: { x: 360, y: 474 },
        logo: { x: 360, y: 270 }
    };
    const positions = structuredClone(originalPositions);
    let logo = null;
    let dragging = null;
    let lastPackageType = null;

    function roundedRect(x, y, width, height, radius) {
        ctx.beginPath();
        ctx.roundRect(x, y, width, height, radius);
    }

    function draw() {
        const w = canvas.width;
        const h = canvas.height;
        const packageType = controls.packageType.value;
        if (lastPackageType !== packageType) {
            setDefaultPositions(packageType);
            lastPackageType = packageType;
        }
        ctx.clearRect(0, 0, w, h);

        const background = ctx.createLinearGradient(0, 0, w, h);
        background.addColorStop(0, '#fbfaf7');
        background.addColorStop(1, '#eee9e1');
        ctx.fillStyle = background;
        ctx.fillRect(0, 0, w, h);

        // Soft floor shadow and a simple three-dimensional box silhouette.
        ctx.fillStyle = 'rgba(53, 39, 23, .16)';
        ctx.beginPath();
        ctx.ellipse(372, 690, 205, 28, 0, 0, Math.PI * 2);
        ctx.fill();

        if (controls.packageType.value === 'bottle') {
            drawBottle();
        } else {
            drawCarton();
        }

        drawLogo();
        drawText('brand', controls.brand.value || 'اسم البراند', '600 17px Arial', 26);
        drawText('perfume', controls.perfume.value || 'اسم العطر', '600 37px Georgia, serif', 120);
        drawText('tagline', controls.tagline.value || 'وصف العطر', '14px Arial', 176);
    }

    function setDefaultPositions(packageType) {
        if (packageType === 'bottle') {
            Object.assign(positions.logo, { x: 360, y: 363 });
            Object.assign(positions.brand, { x: 360, y: 414 });
            Object.assign(positions.perfume, { x: 360, y: 472 });
            Object.assign(positions.tagline, { x: 360, y: 526 });
        } else if (packageType === 'gift') {
            Object.assign(positions.logo, { x: 325, y: 296 });
            Object.assign(positions.brand, { x: 325, y: 355 });
            Object.assign(positions.perfume, { x: 325, y: 425 });
            Object.assign(positions.tagline, { x: 325, y: 482 });
        } else {
            Object.assign(positions.logo, { x: 355, y: 270 });
            Object.assign(positions.brand, { x: 355, y: 330 });
            Object.assign(positions.perfume, { x: 355, y: 418 });
            Object.assign(positions.tagline, { x: 355, y: 474 });
        }
    }

    function drawCarton() {
        const gift = controls.packageType.value === 'gift';
        const x = gift ? 120 : 170;
        const y = gift ? 220 : 145;
        const boxW = gift ? 410 : 370;
        const boxH = gift ? 390 : 520;
        const side = gift ? 62 : 52;
        const centerX = x + boxW / 2;

        ctx.fillStyle = shade(controls.boxColor.value, -38);
        ctx.beginPath();
        ctx.moveTo(x + boxW, y);
        ctx.lineTo(x + boxW + side, y + 28);
        ctx.lineTo(x + boxW + side, y + boxH - 22);
        ctx.lineTo(x + boxW, y + boxH);
        ctx.closePath();
        ctx.fill();

        ctx.fillStyle = shade(controls.boxColor.value, 22);
        ctx.beginPath();
        ctx.moveTo(x, y);
        ctx.lineTo(x + 28, y - 26);
        ctx.lineTo(x + boxW + 25, y - 26);
        ctx.lineTo(x + boxW, y);
        ctx.closePath();
        ctx.fill();

        const front = ctx.createLinearGradient(x, y, x + boxW, y + boxH);
        front.addColorStop(0, shade(controls.boxColor.value, 18));
        front.addColorStop(.48, controls.boxColor.value);
        front.addColorStop(1, shade(controls.boxColor.value, -8));
        ctx.fillStyle = front;
        ctx.fillRect(x, y, boxW, boxH);
        ctx.strokeStyle = 'rgba(48, 37, 26, .22)';
        ctx.lineWidth = 2;
        ctx.strokeRect(x + 1, y + 1, boxW - 2, boxH - 2);
        ctx.strokeStyle = `${controls.inkColor.value}55`;
        ctx.lineWidth = 1.5;
        ctx.strokeRect(x + 25, y + 25, boxW - 50, boxH - 50);

        if (gift) {
            ctx.fillStyle = `${controls.inkColor.value}30`;
            ctx.fillRect(x, y + 54, boxW, 13);
            ctx.fillRect(centerX - 7, y + 54, 14, boxH - 54);
        }
    }

    function drawBottle() {
        // Cap, atomizer neck, glass shoulders, and a colored juice fill.
        const cap = ctx.createLinearGradient(290, 0, 430, 0);
        cap.addColorStop(0, shade(controls.inkColor.value, -20));
        cap.addColorStop(.5, controls.inkColor.value);
        cap.addColorStop(1, shade(controls.inkColor.value, 25));
        ctx.fillStyle = cap;
        roundedRect(306, 112, 108, 104, 17); ctx.fill();
        ctx.fillStyle = '#ffffff55';
        ctx.fillRect(322, 127, 8, 72);

        const neck = ctx.createLinearGradient(315, 0, 405, 0);
        neck.addColorStop(0, '#d6c7ab'); neck.addColorStop(.5, '#fff9e9'); neck.addColorStop(1, '#bba987');
        ctx.fillStyle = neck;
        roundedRect(316, 205, 88, 82, 12); ctx.fill();

        ctx.save();
        ctx.shadowColor = 'rgba(53,39,23,.18)'; ctx.shadowBlur = 24; ctx.shadowOffsetY = 14;
        const glass = ctx.createLinearGradient(210, 280, 500, 640);
        glass.addColorStop(0, '#ffffffd9'); glass.addColorStop(.45, `${controls.boxColor.value}e8`); glass.addColorStop(1, '#ffffffaa');
        ctx.fillStyle = glass;
        ctx.beginPath();
        ctx.moveTo(288, 276); ctx.quadraticCurveTo(360, 292, 432, 276);
        ctx.lineTo(476, 326); ctx.lineTo(476, 632); ctx.quadraticCurveTo(476, 660, 448, 660);
        ctx.lineTo(272, 660); ctx.quadraticCurveTo(244, 660, 244, 632); ctx.lineTo(244, 326); ctx.closePath(); ctx.fill();
        ctx.restore();
        ctx.strokeStyle = '#88775e88'; ctx.lineWidth = 2;
        ctx.beginPath(); ctx.moveTo(288, 276); ctx.quadraticCurveTo(360, 292, 432, 276); ctx.lineTo(476, 326); ctx.lineTo(476, 632); ctx.quadraticCurveTo(476, 660, 448, 660); ctx.lineTo(272, 660); ctx.quadraticCurveTo(244, 660, 244, 632); ctx.lineTo(244, 326); ctx.closePath(); ctx.stroke();
        ctx.fillStyle = '#ffffff70'; roundedRect(264, 340, 14, 270, 7); ctx.fill();

        ctx.fillStyle = `${controls.boxColor.value}eF`;
        ctx.strokeStyle = `${controls.inkColor.value}66`;
        ctx.lineWidth = 1.5;
        roundedRect(268, 333, 184, 235, 8); ctx.fill(); ctx.stroke();
    }

    function drawText(key, value, font, hitHeight) {
        const p = positions[key];
        ctx.save();
        ctx.font = font;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = controls.inkColor.value;
        ctx.shadowColor = 'rgba(255,255,255,.22)';
        ctx.shadowBlur = 1;
        ctx.fillText(value, p.x, p.y, 310);
        const width = Math.min(ctx.measureText(value).width, 310);
        p.hit = { x: p.x - width / 2 - 12, y: p.y - hitHeight / 2, w: width + 24, h: hitHeight };
        ctx.restore();
    }

    function drawLogo() {
        const p = positions.logo;
        const size = 48;
        if (logo) {
            const ratio = Math.min(size / logo.width, size / logo.height);
            const width = logo.width * ratio;
            const height = logo.height * ratio;
            ctx.drawImage(logo, p.x - width / 2, p.y - height / 2, width, height);
            p.hit = { x: p.x - size / 2 - 8, y: p.y - size / 2 - 8, w: size + 16, h: size + 16 };
        } else {
            ctx.save();
            ctx.strokeStyle = controls.inkColor.value;
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.arc(p.x, p.y, 21, 0, Math.PI * 2);
            ctx.stroke();
            ctx.font = '20px Georgia, serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = controls.inkColor.value;
            ctx.fillText('✦', p.x, p.y + 1);
            p.hit = { x: p.x - 29, y: p.y - 29, w: 58, h: 58 };
            ctx.restore();
        }
    }

    function shade(hex, amount) {
        const value = hex.replace('#', '');
        const num = parseInt(value, 16);
        const r = Math.max(0, Math.min(255, (num >> 16) + amount));
        const g = Math.max(0, Math.min(255, ((num >> 8) & 255) + amount));
        const b = Math.max(0, Math.min(255, (num & 255) + amount));
        return `rgb(${r}, ${g}, ${b})`;
    }

    function pointerPosition(event) {
        const rect = canvas.getBoundingClientRect();
        return { x: (event.clientX - rect.left) * canvas.width / rect.width, y: (event.clientY - rect.top) * canvas.height / rect.height };
    }

    function hitTest(point) {
        for (const key of ['tagline', 'perfume', 'brand', 'logo']) {
            const hit = positions[key].hit;
            if (hit && point.x >= hit.x && point.x <= hit.x + hit.w && point.y >= hit.y && point.y <= hit.y + hit.h) return key;
        }
        return null;
    }

    canvas.addEventListener('pointerdown', event => {
        const point = pointerPosition(event);
        const key = hitTest(point);
        if (!key) return;
        dragging = { key, dx: positions[key].x - point.x, dy: positions[key].y - point.y };
        canvas.setPointerCapture(event.pointerId);
    });
    canvas.addEventListener('pointermove', event => {
        if (!dragging) return;
        const point = pointerPosition(event);
        positions[dragging.key].x = Math.max(205, Math.min(505, point.x + dragging.dx));
        positions[dragging.key].y = Math.max(180, Math.min(625, point.y + dragging.dy));
        draw();
    });
    canvas.addEventListener('pointerup', () => dragging = null);
    canvas.addEventListener('pointercancel', () => dragging = null);

    Object.values(controls).forEach(control => {
        control.addEventListener('input', draw);
        control.addEventListener('change', draw);
    });
    controls.logoFile.addEventListener('change', () => {
        const file = controls.logoFile.files?.[0];
        if (!file) { logo = null; draw(); return; }
        if (!file.type.startsWith('image/')) { alert('اختار ملف صورة صالح.'); controls.logoFile.value = ''; return; }
        if (file.size > 3 * 1024 * 1024) { alert('حجم الشعار لازم يكون أقل من 3MB.'); controls.logoFile.value = ''; return; }
        const image = new Image();
        image.onload = () => { logo = image; draw(); };
        image.onerror = () => alert('تعذر فتح ملف الصورة.');
        image.src = URL.createObjectURL(file);
    });

    document.getElementById('resetButton').addEventListener('click', () => {
        for (const key of Object.keys(originalPositions)) {
            Object.assign(positions[key], originalPositions[key]);
        }
        lastPackageType = null;
        draw();
    });

    document.getElementById('downloadButton').addEventListener('click', () => {
        draw();
        const link = document.createElement('a');
        const safeName = (controls.perfume.value || 'perfume-box').trim().replace(/[^\p{L}\p{N}-]+/gu, '-');
        link.download = `${safeName || 'perfume-box'}-preview.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
    });

    draw();
</script>
</body>
</html>
