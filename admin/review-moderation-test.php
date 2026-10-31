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
    <title>اختبار تحليل تقييمات العطور</title>
    <style>
        :root { font-family: Tahoma, Arial, sans-serif; color: #24211f; background: #f4f1ec; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        header { padding: 23px max(18px, calc((100% - 900px) / 2)); background: #29231f; color: #fff; }
        h1 { margin: 0 0 7px; font-size: 24px; }
        header p { margin: 0; color: #ddd4c9; }
        main { max-width: 900px; margin: auto; padding: 20px; }
        .notice { padding: 12px 14px; border: 1px solid #dfc994; border-radius: 8px; background: #fff9e9; line-height: 1.7; }
        .panel { margin-top: 16px; padding: 20px; border: 1px solid #e4ded6; border-radius: 10px; background: #fff; }
        label { display: block; margin-bottom: 7px; font-weight: 700; }
        textarea { width: 100%; min-height: 160px; padding: 12px; border: 1px solid #d4cec6; border-radius: 7px; resize: vertical; font: inherit; line-height: 1.7; }
        .samples, .actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
        button { min-height: 39px; padding: 8px 13px; border: 0; border-radius: 7px; background: #745d3f; color: #fff; font: inherit; cursor: pointer; }
        button.secondary { background: #eee9e2; color: #332c25; }
        button:disabled { opacity: .6; cursor: wait; }
        .hint { margin: 9px 0 0; color: #77716b; font-size: 13px; }
        #result { display: none; margin-top: 16px; padding: 16px; border-radius: 8px; line-height: 1.8; }
        #result.flagged { display: block; border: 1px solid #e2b7aa; background: #fff0ec; }
        #result.safe { display: block; border: 1px solid #b8d8bf; background: #eff9f0; }
        #result.error { display: block; border: 1px solid #e2b7aa; background: #fff0ec; color: #812e22; }
        .result-title { margin: 0 0 6px; font-weight: 700; }
        @media (max-width: 600px) { main { padding: 12px; } .panel { padding: 15px; } }
    </style>
</head>
<body>
<header>
    <h1>اختبار AI لمراجعة التقييمات</h1>
    <p>جرّب نصوصاً مختلفة وشوف هل النظام يطلب مراجعة بشرية وليش.</p>
</header>
<main>
    <div class="notice"><strong>اختبار مستقل:</strong> النص يُرسل لخدمة AI للتحليل فقط. لا يتم إنشاء Review أو حفظ النص في قاعدة البيانات أو إرسال إشعار للأدمن.</div>
    <section class="panel">
        <label for="reviewText">نص التقييم</label>
        <textarea id="reviewText" maxlength="3000" placeholder="اكتب أو الصق تقييماً بالعربي أو الإنجليزي..."></textarea>
        <p class="hint"><span id="characterCount">0</span> / 3000 حرف</p>
        <div class="samples" aria-label="أمثلة للتجربة">
            <button class="secondary sample" data-sample="الرائحة جميلة وثباته ممتاز، لكن السعر مرتفع شوي.">مثال طبيعي</button>
            <button class="secondary sample" data-sample="هذا المنتج سيئ جداً وخدمتكم سيئة، لن أشتري مرة أخرى.">رأي سلبي بدون إساءة</button>
            <button class="secondary sample" data-sample="أنت غبي ومحتال، سأجدك وأؤذيك.">مثال يحتاج مراجعة</button>
            <button class="secondary sample" data-sample="اشتروا من موقعي واحصلوا على خصم كبير، راسلوني على الرقم 0791234567.">إعلان ومعلومات تواصل</button>
            <button class="secondary sample" data-sample="The perfume smells fresh and lasts about six hours. Nice bottle too.">English example</button>
        </div>
        <div class="actions"><button id="analyzeButton" type="button">حلّل التقييم</button><button id="clearButton" class="secondary" type="button">مسح النص والنتيجة</button></div>
        <div id="result" role="status" aria-live="polite"></div>
    </section>
</main>
<script>
    const textArea = document.getElementById('reviewText');
    const resultBox = document.getElementById('result');
    const analyzeButton = document.getElementById('analyzeButton');

    textArea.addEventListener('input', () => {
        document.getElementById('characterCount').textContent = String(textArea.value.length);
    });

    document.querySelectorAll('.sample').forEach(button => {
        button.addEventListener('click', () => {
            textArea.value = button.dataset.sample;
            textArea.dispatchEvent(new Event('input'));
            resultBox.className = '';
            resultBox.textContent = '';
        });
    });

    document.getElementById('clearButton').addEventListener('click', () => {
        textArea.value = '';
        textArea.dispatchEvent(new Event('input'));
        resultBox.className = '';
        resultBox.textContent = '';
    });

    analyzeButton.addEventListener('click', async () => {
        resultBox.className = '';
        resultBox.textContent = '';
        if (!textArea.value.trim()) {
            showError('اكتب نص تقييم أولاً.');
            return;
        }

        analyzeButton.disabled = true;
        analyzeButton.textContent = 'جاري التحليل...';
        try {
            const response = await fetch('../api/test-review-moderation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ text: textArea.value })
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.message || 'فشل تحليل التقييم.');

            const data = payload.data;
            resultBox.className = data.flagged ? 'flagged' : 'safe';
            const title = document.createElement('p');
            title.className = 'result-title';
            title.textContent = data.flagged ? 'النتيجة: يحتاج مراجعة الأدمن' : 'النتيجة: يبدو مناسباً للنشر';
            const category = document.createElement('p');
            category.textContent = `التصنيف: ${categoryName(data.category)}`;
            const reason = document.createElement('p');
            reason.textContent = `السبب: ${data.reason}`;
            resultBox.append(title, category, reason);
        } catch (error) {
            showError(error.message || 'تعذر الاتصال بخدمة التحليل.');
        } finally {
            analyzeButton.disabled = false;
            analyzeButton.textContent = 'حلّل التقييم';
        }
    });

    function categoryName(category) {
        const names = {
            none: 'لا يوجد سبب واضح للمراجعة',
            abusive_language: 'لغة مسيئة',
            hate_or_discrimination: 'كراهية أو تمييز',
            threat: 'تهديد',
            spam_or_advertising: 'سبام أو إعلان',
            personal_information: 'معلومات شخصية',
            irrelevant_content: 'محتوى غير متعلق بالمنتج',
            other: 'سبب آخر'
        };
        return names[category] || category;
    }

    function showError(message) {
        resultBox.className = 'error';
        resultBox.textContent = message;
    }
</script>
</body>
</html>
