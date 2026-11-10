{{-- Tokhoom's own landing page, shown to guests on the dashboard domain. --}}
@php
    use App\Enums\Plan;
    use App\Support\Arabic;

    $demoSite = config('tokhoom.demo_site_url');
    $seller = config('tokhoom.seller');
    $support = config('tokhoom.support');
    $whatsappUrl = $support['whatsapp'] ? 'https://wa.me/'.preg_replace('/\D/', '', $support['whatsapp']) : null;

    $facts = [
        ['لمن', 'مكاتب العقار والوسطاء المرخّصون'],
        ['السعر', 'من '.Plan::Agent->monthlyPrice().' ريالاً شهرياً'],
        ['العمولة على الصفقات', 'لا شيء'],
        ['التجربة', config('tokhoom.trial_days').' يوماً، ويظهر الموقع بعد مراجعة الترخيص'],
    ];

    $siteDomain = config('tokhoom.site_domain');

    $steps = [
        ['اختر عنوانك', 'موقع يعرض عقاراتك وحدها، على عنوان باسمك:'],
        ['أضف رخصة فال وشعارك ولونك', 'تظهر رخصة فال للزوار في رأس الموقع، ويُنشر الموقع بعد مراجعة الترخيص.'],
        ['أضف عقاراتك وشارك الرابط', 'أضف العقار من الجوال مع ترخيص إعلانه، وشارك رابطه في واتساب بصورة ووصف، أو اطبع رمز QR.'],
    ];

    $addressRows = [
        ['.'.$siteDomain, 'موقع مكتبك، جاهز من اليوم الأول.', 'كل الخطط'],
        ['.'.$siteDomain.'/agents/12', 'صفحة لكل وسيط في مكتبك بعقاراته ورقم رخصته.', 'المكتب والمكتب بلس'],
        ['.sa', 'اربط نطاقك الخاص، ويبقى عنوان تخوم يعمل.', 'المكتب بلس'],
    ];

    $isList = [
        'موقع مستقل لكل مكتب أو وسيط، بعنوانه وشعاره ولونه.',
        'لوحة تحكم للعقارات والعملاء والصفقات والفريق والتقارير.',
        'اشتراك شهري ثابت يحدده عدد المقاعد.',
        'أداة تفرض إدخال ترخيص الإعلان قبل النشر.',
    ];

    $notList = [
        'منصة إعلانات تجمع عقارات المكاتب في بحث واحد.',
        'وسيط بين المشتري والبائع، أو جهة تستلم أي مبالغ.',
        'مكان تُعرض فيه عقارات منافسيك بجانب عقاراتك.',
    ];

    $parts = [
        [
            'mock' => 'site',
            'title' => 'موقع يعرض عقاراتك وحدها',
            'body' => 'يرفع المكتب شعاره ويختار لوناً واحداً، ويتكيّف الموقع معه. لكل وسيط صفحة بعقاراته ورقم رخصته، ولكل عقار صفحة فيها الصور والتفاصيل وزر واتساب.',
            'specs' => [
                ['العنوان', 'alrashid.'.$siteDomain.' أو نطاقك الخاص'],
                ['صفحات الوسطاء', 'صفحة لكل وسيط في المكتب'],
                ['البحث والتصفية', 'الغرض، النوع، الحي، السعر، الغرف'],
                ['التواصل', 'واتساب، اتصال، ونموذج استفسار'],
            ],
        ],
        [
            'mock' => 'editor',
            'title' => 'أضف عقاراً من الجوال في شاشة واحدة',
            'body' => 'ارفع الصور ورتّبها، واختر الغرض والنوع والحي، وأدخل السعر والمساحة. زر النشر لا يعمل قبل إدخال رقم ترخيص الإعلان.',
            'specs' => [
                ['الصور', 'حتى '.\App\Models\Listing::MAX_PHOTOS.' صورة، والأولى هي الغلاف'],
                ['الحالة', 'متاح، محجوز، مباع، مؤجّر، مخفي'],
                ['المشاركة', 'رابط قصير، رمز QR بصيغة PNG وSVG'],
                ['الوسائط', 'رابط جولة ثلاثية الأبعاد وفيديو'],
            ],
        ],
        [
            'mock' => 'crm',
            'title' => 'تابع كل عميل حتى إغلاق الصفقة',
            'body' => 'سجّل طلب العميل مرة واحدة، ويخبرك تخوم عندما يُضاف عقار يناسبه. انقل الصفقة بين المراحل وسجّل السعر النهائي والعمولة عند الإغلاق.',
            'specs' => [
                ['التطابقات', 'حسب الغرض والنوع والحي والميزانية'],
                ['التذكيرات', 'متابعات يومية لكل وسيط'],
                ['التقارير', 'الزيارات، مصادر العملاء، زمن الرد، العمولة'],
                ['الفريق', 'أدوار، دعوات، ونقل بيانات الوسيط عند مغادرته'],
            ],
        ],
    ];

    $compliance = [
        ['رخصة فال للمنشأة أو الوسيط', 'نراجع الرقم وتاريخ الانتهاء قبل ظهور الموقع، وننبهك قبل الانتهاء بـ 60 و30 و7 أيام.', 'رأس الموقع، صفحة الوسيط، تذييل كل صفحة'],
        ['ترخيص الإعلان لكل عقار', 'حقل إلزامي قبل النشر. عند انتهاء الترخيص يُخفى العقار تلقائياً ونبلغك.', 'صفحة العقار، بجانب رخصة الوسيط'],
        ['السجل التجاري للمكتب', 'يُطلب عند التسجيل كمكتب ويُراجع مع رخصة فال.', 'شريط التراخيص في الصفحة الرئيسية'],
        ['خصوصية موقع العقار', 'يرى الزائر منطقة تقريبية فقط، والموقع الدقيق للمكتب وحده.', 'خريطة صفحة العقار'],
        ['موافقة العميل على التواصل', 'نموذج الاستفسار لا يُرسل بدون موافقة صريحة من الزائر.', 'نموذج الاستفسار'],
    ];

    $compare = [
        ['اسم مكتبك وهويته', 'شعار صغير داخل صفحة المنصة', 'كامل', 'كامل'],
        ['عقارات المنافسين', 'تظهر بجانب عقاراتك', 'لا تظهر', 'لا تظهر'],
        ['التكلفة', 'رسوم لكل إعلان أو باقة', 'تكلفة تطوير ثم صيانة', 'اشتراك شهري ثابت'],
        ['إدارة العملاء والصفقات', 'غير متوفرة غالباً', 'تحتاج نظاماً منفصلاً', 'مضمّنة'],
        ['تنبيهات التراخيص', 'حسب المنصة', 'تحتاج تطويراً', 'مضمّنة'],
    ];

    $planRows = [
        Plan::Agent->value => [['المقاعد', '1'], ['العقارات النشطة', (string) Plan::Agent->listingLimit()], ['صفحات الوسطاء', '—'], ['النطاق الخاص', '—']],
        Plan::Office->value => [['المقاعد', (string) Plan::Office->includedSeats()], ['العقارات النشطة', 'غير محدودة'], ['صفحات الوسطاء', 'نعم'], ['النطاق الخاص', '—']],
        Plan::Plus->value => [['المقاعد', (string) Plan::Plus->includedSeats()], ['العقارات النشطة', 'غير محدودة'], ['صفحات الوسطاء', 'نعم'], ['النطاق الخاص', 'نعم']],
    ];

    $faqs = [
        'lic' => ['التراخيص', [
            ['هل أحتاج رخصة فال للتسجيل؟', 'نعم. نطلب رقم رخصة فال للمنشأة أو للوسيط، ورقم السجل التجاري للمكاتب. لا يظهر الموقع للزوار قبل مراجعة البيانات.'],
            ['ماذا يحدث عند انتهاء ترخيص إعلان عقار؟', 'يُخفى العقار من الموقع تلقائياً ويصلك تنبيه، ويعود للظهور عند تحديث رقم الترخيص.'],
            ['هل يظهر موقع العقار بدقة على الخريطة؟', 'لا. يرى الزائر دائرة تقريبية حول الحي، ويبقى الموقع الدقيق داخل لوحة التحكم.'],
        ]],
        'plan' => ['الاشتراك', [
            ['هل تأخذون عمولة على الصفقات؟', 'لا. تدفع الاشتراك فقط، والتواصل مع العملاء يتم مباشرة عبر واتساب أو الاتصال.'],
            ['ماذا يحدث بعد انتهاء التجربة؟', 'تصدر فاتورة الشهر الأول، وتدفعها بمدى أو Apple Pay أو البطاقة من صفحة الاشتراك. لا نخصم من بطاقة لم تحفظها بنفسك.'],
            ['هل يمكنني تغيير الخطة أو إضافة مقاعد؟', 'نعم من صفحة الاشتراك. المقعد الإضافي بـ '.Plan::EXTRA_SEAT_PRICE.' ريالاً شهرياً.'],
        ]],
        'domain' => ['العنوان والنطاق', [
            ['هل أستطيع تغيير عنوان موقعي لاحقاً؟', 'لا. يثبت العنوان بعد إنشاء الحساب، لذلك اختره بعناية. ويمكنك ربط نطاقك الخاص في خطة المكتب بلس.'],
            ['هل أحتاج نطاقاً خاصاً؟', 'لا. يعمل كل موقع على عنوانه في '.$siteDomain.' من اليوم الأول.'],
            ['كيف أربط نطاقي الخاص؟', 'أضف سجل CNAME واحداً عند مزوّد نطاقك. تعرض لك لوحة التحكم السجل المطلوب بالضبط وتتحقق منه بنفسها.'],
            ['ماذا يحدث لعنوان تخوم بعد ربط نطاقي؟', 'يبقى العنوانان يعملان، ويفتح كلاهما موقعك نفسه.'],
        ]],
        'data' => ['البيانات', [
            ['لمن تعود بيانات العملاء؟', 'للمكتب. لا نستخدم بيانات عملائك في أي مكان آخر، ولا نعرض عقاراتك خارج موقعك.'],
            ['هل يمكنني تصدير تقاريري؟', 'نعم. صدّر تقرير أداء الوسطاء لأي فترة بملف يفتح في Excel.'],
            ['ماذا يحدث لبيانات الوسيط إذا غادر المكتب؟', 'عند إيقاف حسابه تختار من يستلم عقاراته وعملاءه وصفقاته، ولا يفقد المكتب شيئاً.'],
        ]],
    ];

    $company = array_values(array_filter([
        ['الاسم النظامي', $seller['name'], 'rtl'],
        ['المقر', $seller['address'], 'rtl'],
        ['السجل التجاري', $seller['cr_number'], 'ltr'],
        ['الدعم', $support['hours'], 'rtl'],
        ['الهاتف', $support['phone'], 'ltr'],
        ['البريد', $support['email'], 'ltr'],
    ], fn ($row) => filled($row[1])));
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تخوم · موقع عقاري باسم مكتبك</title>
    <x-favicons />
    <meta name="description" content="تخوم اشتراك شهري لمكاتب العقار والوسطاء المرخّصين في السعودية: موقع يعرض عقاراتك وحدها، ولوحة تحكم للعملاء والصفقات والفريق.">
    <meta name="theme-color" content="#145a55">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="تخوم">
    <meta property="og:title" content="تخوم · موقع عقاري باسم مكتبك">
    <meta property="og:url" content="{{ url('/') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/site.js'])
    @livewireStyles
</head>
<body class="bg-canvas" data-site-domain="{{ $siteDomain }}" data-signup-url="{{ route('signup') }}">
    <header class="sticky top-0 z-20 border-b border-line bg-canvas/95 backdrop-blur" x-data>
        <div class="mx-auto flex min-h-[68px] max-w-[1160px] items-center gap-8 px-4 lg:px-5">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 hover:no-underline">
                <x-logo class="size-8" />
                <span class="text-22 font-bold text-primary">تخوم</span>
            </a>
            <nav class="hidden flex-1 gap-7 text-15 lg:flex">
                <a href="#product" class="text-body">المنتج</a>
                <a href="#compliance" class="text-body">التراخيص</a>
                <a href="#pricing" class="text-body">الأسعار</a>
                <a href="#faq" class="text-body">الأسئلة</a>
                <a href="#company" class="text-body">عن تخوم</a>
                @if ($demoSite)
                    <a href="{{ $demoSite }}" class="flex items-center gap-1 font-semibold">زر موقعاً نموذجياً<i class="ti ti-external-link text-16"></i></a>
                @endif
            </nav>
            <div class="ms-auto flex items-center gap-1.5">
                <a href="{{ route('login') }}" class="flex h-11 items-center whitespace-nowrap px-3.5 text-15 font-semibold text-ink">دخول</a>
                <a href="{{ route('signup') }}" x-on:click="$store.claim.start($event)" class="flex h-11 items-center whitespace-nowrap rounded-field bg-primary px-4.5 text-15 font-semibold text-white hover:bg-primary-strong hover:text-white hover:no-underline">ابدأ التجربة</a>
            </div>
        </div>
    </header>

    <main x-data>
        {{-- Hero --}}
        <section class="relative isolate flex min-h-[calc(100dvh-69px)] items-center overflow-hidden bg-primary-deep text-white">
            <div class="absolute inset-0 -z-10 bg-[linear-gradient(rgba(255,255,255,0.05)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.05)_1px,transparent_1px)] bg-[size:56px_56px] [mask-image:radial-gradient(ellipse_at_center,black_30%,transparent_75%)]" aria-hidden="true"></div>
            <div class="absolute left-1/2 top-1/2 -z-10 size-[720px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary-mid/40 blur-[120px]" aria-hidden="true"></div>

            <div class="mx-auto flex w-full max-w-[1160px] flex-col items-center gap-7 px-4 py-10 text-center lg:gap-9 lg:px-5">
                <h1 class="text-[clamp(22px,2.6vw,36px)] font-bold leading-[1.4] text-balance lg:whitespace-nowrap">موقع عقاري باسم مكتبك، ولوحة تحكم تدير بها عملك اليومي.</h1>

                <div id="start" class="w-full max-w-[760px] scroll-mt-24 rounded-card bg-white p-4 text-start text-ink shadow-[0_24px_60px_rgba(0,0,0,0.25)] sm:p-6">
                    <livewire:claim-site-address />
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-[1160px] px-4 pt-14 lg:px-5 lg:pt-24">
            <dl class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($facts as [$label, $value])
                    <div class="flex flex-col gap-1 border-t border-line-strong pt-3.5">
                        <dt class="text-13 text-muted">{{ $label }}</dt>
                        <dd class="text-16 font-semibold leading-normal">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- How it works --}}
        <section class="mx-auto flex max-w-[1160px] flex-col gap-8 px-4 pt-18 lg:px-5 lg:pt-30">
            <h2 class="text-[clamp(26px,3vw,34px)] font-bold leading-[1.4] text-balance">ثلاث خطوات حتى يظهر موقعك</h2>
            <ol class="grid gap-x-8 gap-y-6 md:grid-cols-3">
                @foreach ($steps as [$title, $body])
                    <li class="flex flex-col gap-3 border-t-2 border-primary pt-5">
                        <span class="flex items-center gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-soft text-16 font-bold text-primary">{{ $loop->iteration }}</span>
                            <h3 class="text-[19px] font-bold leading-snug">{{ $title }}</h3>
                        </span>
                        <p class="text-16 leading-[1.8] text-body text-pretty">
                            {{ $body }}
                            @if ($loop->first)
                                <span class="mt-1 block truncate text-start font-mono text-15 font-semibold text-ink" dir="ltr" x-text="$store.claim.host">alrashid.{{ $siteDomain }}</span>
                            @endif
                        </p>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Product --}}
        <section id="product" class="mx-auto flex max-w-[1160px] scroll-mt-20 flex-col gap-14 px-4 pt-18 lg:gap-24 lg:px-5 lg:pt-30">
            @foreach ($parts as $part)
                <article class="grid items-start gap-7 border-t border-line-strong pt-7 lg:grid-cols-2 lg:gap-14">
                    <div class="flex flex-col gap-4.5">
                        <h2 class="text-[clamp(24px,2.8vw,32px)] font-bold leading-[1.4] text-balance">{{ $part['title'] }}</h2>
                        <p class="max-w-[520px] text-16 leading-[1.85] text-body text-pretty">{{ $part['body'] }}</p>
                        <dl class="mt-1.5 flex flex-col">
                            @foreach ($part['specs'] as [$label, $value])
                                <div class="grid grid-cols-[minmax(110px,150px)_minmax(0,1fr)] gap-4 border-t border-line py-3">
                                    <dt class="text-14 text-muted">{{ $label }}</dt>
                                    <dd class="text-15 leading-[1.65]">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    <div class="flex flex-col gap-3 rounded-card border border-line bg-white p-4" aria-hidden="true">
                        @if ($part['mock'] === 'site')
                            <div class="grid grid-cols-2 gap-2.5">
                                @foreach ([['للبيع', '3,200,000 ريال', 'فيلا · حي الملقا، الرياض', '450 م² · 5 غرف · 6 دورات مياه'], ['للإيجار', '65,000 ريال سنوياً', 'شقة · حي النرجس، الرياض', '160 م² · 3 غرف · دورتا مياه']] as [$purpose, $price, $text, $meta])
                                    <div class="overflow-hidden rounded-field border border-line">
                                        <div class="photo-stripes-lg relative aspect-[4/3]"><span class="absolute right-2 top-2 rounded-full bg-white px-2.5 py-0.5 text-11 font-bold text-primary">{{ $purpose }}</span></div>
                                        <div class="flex flex-col gap-0.5 px-3 py-2.5"><span class="text-15 font-bold">{{ $price }}</span><span class="text-12 text-body">{{ $text }}</span><span class="text-12 text-muted">{{ $meta }}</span></div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex items-center gap-2.5 rounded-field border border-line px-3 py-2.5">
                                <span class="photo-stripes size-10 shrink-0 rounded-full"></span>
                                <span class="flex flex-1 flex-col"><span class="text-14 font-semibold">سارة القحطاني</span><span class="text-12 text-muted">وسيطة عقارية</span></span>
                                <span class="text-12 text-body">فال 1100012345</span>
                            </div>
                        @elseif ($part['mock'] === 'editor')
                            <div class="grid grid-cols-4 gap-1.5">
                                <span class="photo-stripes relative aspect-square rounded-lg border-2 border-primary"><span class="absolute right-1 top-1 rounded-full bg-primary px-1.5 text-[10px] font-bold text-white">الغلاف</span></span>
                                <span class="photo-stripes aspect-square rounded-lg"></span>
                                <span class="photo-stripes aspect-square rounded-lg"></span>
                                <span class="flex aspect-square items-center justify-center rounded-lg border-[1.5px] border-dashed border-faint text-primary"><i class="ti ti-camera-plus text-20"></i></span>
                            </div>
                            <div class="grid grid-cols-2 gap-1.5">
                                <span class="rounded-field border-[1.5px] border-primary bg-primary-soft p-3 text-center text-14 font-semibold text-primary-strong">للبيع</span>
                                <span class="rounded-field border border-line-strong p-3 text-center text-14">للإيجار</span>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <span class="text-13 font-medium">رقم ترخيص الإعلان</span>
                                <span class="flex items-center justify-between rounded-field border-[1.5px] border-success px-3.5 py-3 text-15" dir="ltr">7200098765<i class="ti ti-circle-check text-18 text-success"></i></span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <span class="rounded-field border border-line-strong p-3 text-center text-14 font-semibold">حفظ كمسودة</span>
                                <span class="rounded-field bg-primary p-3 text-center text-14 font-semibold text-white">نشر</span>
                            </div>
                        @else
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ([['معاينة', 'عبدالله الدوسري', 'شقة · النرجس', '65,000'], ['تفاوض', 'منيرة السبيعي', 'فيلا · الملقا', '3,200,000'], ['ناجحة', 'نوف المطيري', 'فيلا · القيروان', '180,000']] as [$stage, $client, $listing, $value])
                                    <div class="flex flex-col gap-1.5 rounded-field bg-divider p-2">
                                        <span class="px-1 text-12 font-bold">{{ $stage }}</span>
                                        <div class="flex flex-col gap-0.5 rounded-lg border border-line bg-white p-2"><span class="text-12 font-semibold">{{ $client }}</span><span class="text-11 text-body">{{ $listing }}</span><span class="text-12 font-bold text-primary">{{ $value }}</span></div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="overflow-hidden rounded-field border border-line">
                                <div class="grid grid-cols-[1.4fr_1fr_1fr_1fr] gap-2 bg-head px-3 py-2 text-12 font-semibold text-muted"><span>الوسيط</span><span>زمن الرد</span><span>عملاء</span><span>صفقات</span></div>
                                @foreach ([['سارة القحطاني', '12 د', '38', '2'], ['فهد العتيبي', '26 د', '27', '1'], ['نورة الشهري', '41 د', '9', '0']] as $row)
                                    <div class="grid grid-cols-[1.4fr_1fr_1fr_1fr] gap-2 border-t border-divider px-3 py-2 text-13">@foreach ($row as $cell)<span>{{ $cell }}</span>@endforeach</div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </section>

        {{-- Address --}}
        <section class="mx-auto flex max-w-[1160px] flex-col gap-8 px-4 pt-18 lg:px-5 lg:pt-30">
            <h2 class="text-[clamp(26px,3vw,34px)] font-bold leading-[1.4] text-balance">عنوانك يكبر معك</h2>
            <div class="overflow-hidden rounded-card border border-line bg-white">
                @foreach ($addressRows as [$suffix, $line, $planNames])
                    <div class="grid gap-x-6 gap-y-1.5 border-t border-divider px-5 py-4.5 first:border-t-0 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1.6fr)_auto] lg:items-center">
                        <span class="truncate text-start font-mono text-16 font-semibold text-primary-strong" dir="ltr"><span x-text="$store.claim.name">alrashid</span>{{ $suffix }}</span>
                        <span class="text-15 leading-[1.7] text-ink-soft">{{ $line }}</span>
                        <span class="w-fit rounded-full bg-divider px-3 py-1 text-13 font-semibold text-body">{{ $planNames }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Compliance --}}
        <section id="compliance" class="mt-18 scroll-mt-20 border-y border-line bg-white lg:mt-30">
            <div class="mx-auto flex max-w-[1160px] flex-col gap-9 px-4 py-14 lg:px-5 lg:py-24">
                <h2 class="text-[clamp(26px,3vw,34px)] font-bold leading-[1.4] text-balance">التراخيص جزء من المنتج، لا ملاحظة في آخره</h2>
                <div class="overflow-hidden rounded-card border border-line">
                    <div class="hidden grid-cols-[1fr_1.6fr_1fr] gap-6 bg-head px-5 py-3.5 text-13 font-semibold text-muted lg:grid"><span>المتطلب</span><span>ما يفعله تخوم</span><span>أين يظهر</span></div>
                    @foreach ($compliance as [$requirement, $how, $where])
                        <div class="grid gap-x-6 gap-y-1.5 border-t border-divider px-5 py-4 first:border-t-0 lg:grid-cols-[1fr_1.6fr_1fr] lg:first:border-t">
                            <span class="text-15 font-semibold leading-relaxed">{{ $requirement }}</span>
                            <span class="text-15 leading-[1.7] text-ink-soft">{{ $how }}</span>
                            <span class="text-14 leading-[1.7] text-muted">{{ $where }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- What it is and isn't --}}
        <section class="mx-auto flex max-w-[1160px] flex-col gap-7 px-4 pt-18 lg:px-5 lg:pt-30">
            <h2 class="text-[clamp(26px,3vw,34px)] font-bold leading-[1.4] text-balance">ما هو تخوم، وما ليس هو</h2>
            <div class="grid gap-x-12 gap-y-6 md:grid-cols-2">
                <div class="flex flex-col">
                    <span class="border-b-2 border-primary pb-3 text-14 font-bold text-primary">تخوم هو</span>
                    @foreach ($isList as $line)
                        <p class="border-b border-line py-3.5 text-16 leading-[1.75]">{{ $line }}</p>
                    @endforeach
                </div>
                <div class="flex flex-col">
                    <span class="border-b-2 border-faint pb-3 text-14 font-bold text-muted">تخوم ليس</span>
                    @foreach ($notList as $line)
                        <p class="border-b border-line py-3.5 text-16 leading-[1.75] text-body">{{ $line }}</p>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Comparison --}}
        <section class="mx-auto flex max-w-[1160px] flex-col gap-8 px-4 pt-18 lg:px-5 lg:pt-30">
            <h2 class="text-[clamp(26px,3vw,34px)] font-bold leading-[1.4] text-balance">مقارنة بالخيارات المعتادة</h2>
            <div class="overflow-x-auto rounded-card border border-line bg-white">
                <div class="min-w-[720px]">
                    <div class="grid grid-cols-[1.3fr_1fr_1fr_1fr] gap-4 border-b border-line px-5 py-4 text-14 font-bold">
                        <span></span><span class="text-muted">منصة إعلانات عامة</span><span class="text-muted">موقع مخصص من مطوّر</span><span class="text-primary">تخوم</span>
                    </div>
                    @foreach ($compare as [$label, $platform, $custom, $tokhoom])
                        <div class="grid grid-cols-[1.3fr_1fr_1fr_1fr] gap-4 border-t border-divider px-5 text-14 leading-relaxed first:border-t-0">
                            <span class="py-3.5 font-semibold">{{ $label }}</span>
                            <span class="py-3.5 text-body">{{ $platform }}</span>
                            <span class="py-3.5 text-body">{{ $custom }}</span>
                            <span class="bg-primary-tint px-3 py-3.5 font-semibold text-primary-strong">{{ $tokhoom }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Pricing --}}
        <section id="pricing" class="mx-auto flex max-w-[1160px] scroll-mt-20 flex-col gap-8 px-4 pt-18 lg:px-5 lg:pt-30">
            <h2 class="text-[clamp(26px,3vw,34px)] font-bold leading-[1.4]">الأسعار</h2>
            <div class="grid overflow-hidden rounded-card border border-line bg-white md:grid-cols-3">
                @foreach ($plans as $plan)
                    @php $popular = $plan === Plan::Office; @endphp
                    <div @class(['flex flex-col gap-4.5 border-t-3 border-line p-7 md:border-l md:last:border-l-0', 'border-t-primary' => $popular, 'border-t-transparent' => ! $popular, 'max-md:border-b' => ! $loop->last])>
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="text-[19px] font-bold">{{ $plan->shortLabel() }}</span>
                            @if ($popular)
                                <span class="rounded-full bg-sand-soft px-2.5 py-0.5 text-12 font-semibold text-notice-action">الأكثر اختياراً</span>
                            @endif
                        </span>
                        <span class="flex items-baseline gap-1.5">
                            <span class="text-[40px] font-bold leading-[1.1]">{{ Arabic::number($plan->monthlyPrice()) }}</span>
                            <span class="text-14 text-muted">ريال شهرياً</span>
                        </span>
                        <dl class="flex flex-1 flex-col">
                            @foreach ($planRows[$plan->value] as [$label, $value])
                                @php $domainRow = $label === 'النطاق الخاص'; @endphp
                                <div @class(['flex justify-between gap-3 border-t border-divider py-2.5 text-14', '-mx-3 rounded-lg border-t-transparent bg-primary-tint px-3' => $domainRow])>
                                    <dt @class(['text-muted' => ! $domainRow, 'font-semibold text-primary-strong' => $domainRow])>{{ $label }}</dt>
                                    <dd @class(['text-left font-semibold', 'text-primary-strong' => $domainRow && $value !== '—'])>{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        <a href="{{ route('signup') }}" x-on:click="$store.claim.start($event)" @class([
                            'rounded-field border p-3.5 text-center text-15 font-semibold hover:no-underline',
                            'border-primary bg-primary text-white hover:bg-primary-strong hover:text-white' => $popular,
                            'border-line-strong bg-white text-ink hover:text-ink' => ! $popular,
                        ])>ابدأ التجربة</a>
                    </div>
                @endforeach
            </div>
            <div class="grid gap-x-8 gap-y-3 text-14 leading-[1.7] text-body md:grid-cols-3">
                <span>كل الخطط تشمل: الموقع، العملاء، الصفقات، المشاركة ورموز QR.</span>
                <span>الأسعار شاملة ضريبة القيمة المضافة. مقعد إضافي {{ Plan::EXTRA_SEAT_PRICE }} ريالاً شهرياً.</span>
                <span>بعد التجربة تختار الخطة وتدفع من صفحة الاشتراك.</span>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="mx-auto grid max-w-[1160px] scroll-mt-20 items-start gap-x-14 gap-y-8 px-4 pt-18 lg:grid-cols-3 lg:px-5 lg:pt-30"
                 x-data="{ tab: 'lic', open: 0 }">
            <div class="flex flex-col gap-5">
                <h2 class="text-[clamp(26px,3vw,34px)] font-bold leading-[1.4]">الأسئلة الشائعة</h2>
                <div class="flex flex-col gap-1" role="tablist">
                    @foreach ($faqs as $key => [$label, $items])
                        <button type="button" role="tab" @click="tab = @js($key); open = 0" :aria-selected="tab === @js($key)"
                                class="flex min-h-11 items-center justify-between gap-2 rounded-field px-3.5 py-2 text-right text-15 font-semibold"
                                :class="tab === @js($key) ? 'bg-primary-soft text-primary-strong' : 'text-body'">
                            {{ $label }}<span class="text-13 font-medium opacity-75">{{ count($items) }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="border-t border-line-strong lg:col-span-2">
                @foreach ($faqs as $key => [$label, $items])
                    <div x-show="tab === @js($key)" @if ($key !== 'lic') x-cloak @endif>
                        @foreach ($items as $index => [$question, $answer])
                            <div class="border-b border-line">
                                <button type="button" @click="open = open === {{ $index }} ? -1 : {{ $index }}" :aria-expanded="open === {{ $index }}"
                                        class="flex w-full items-center justify-between gap-4 py-5 text-right text-17 font-semibold leading-relaxed text-ink">
                                    {{ $question }}
                                    <i class="ti shrink-0 text-20 text-primary" :class="open === {{ $index }} ? 'ti-minus' : 'ti-plus'"></i>
                                </button>
                                <p x-show="open === {{ $index }}" @if ($index !== 0) x-cloak @endif class="mb-5.5 max-w-[680px] text-16 leading-[1.85] text-body text-pretty">{{ $answer }}</p>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Final address form --}}
        <section class="mx-auto max-w-[1160px] px-4 pt-18 lg:px-5 lg:pt-30">
            <div class="mx-auto flex max-w-[720px] flex-col gap-6 rounded-2xl bg-primary-soft p-5 sm:p-8 lg:p-10">
                <h2 class="text-[clamp(24px,2.8vw,32px)] font-bold leading-[1.4] text-balance">اختر عنوان موقعك وابدأ التجربة</h2>
                <div class="rounded-card border border-line bg-white p-4 sm:p-5">
                    <livewire:claim-site-address :with-label="false" />
                </div>
            </div>
        </section>

        {{-- Company --}}
        <section id="company" class="mx-auto max-w-[1160px] scroll-mt-20 px-4 py-18 lg:px-5 lg:py-30">
            <div class="grid overflow-hidden rounded-2xl border border-line bg-white lg:grid-cols-2">
                <div class="flex flex-col gap-4.5 border-line p-7 lg:border-l lg:p-12">
                    <h2 class="text-[clamp(24px,2.8vw,30px)] font-bold leading-[1.4]">من يقف خلف تخوم</h2>
                    <p class="text-16 leading-[1.85] text-body text-pretty">شركة سعودية تبني أدوات لقطاع الوساطة العقارية. فريق الدعم يرد بالعربية خلال ساعات العمل.</p>
                    @if ($whatsappUrl)
                        <div class="mt-1.5 flex flex-wrap gap-2.5">
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="flex items-center gap-2 whitespace-nowrap rounded-field border border-line-strong px-4.5 py-3 text-15 font-semibold text-ink hover:text-ink hover:no-underline"><i class="ti ti-brand-whatsapp text-[19px]"></i>تحدث مع الفريق</a>
                        </div>
                    @endif
                </div>
                <dl class="flex flex-col bg-canvas p-7 lg:p-12">
                    @foreach ($company as [$label, $value, $direction])
                        <div class="grid grid-cols-[minmax(110px,140px)_minmax(0,1fr)] gap-4 border-b border-line py-3">
                            <dt class="text-14 text-muted">{{ $label }}</dt>
                            <dd class="text-right text-15 font-medium leading-relaxed" dir="{{ $direction }}">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    </main>

    <footer class="border-t border-line bg-white">
        <div class="mx-auto flex max-w-[1160px] flex-col gap-7 px-4 pb-7 pt-10 lg:px-5">
            <div class="flex flex-wrap justify-between gap-7">
                <span class="flex items-center gap-2.5"><x-logo class="size-7" /><span class="text-20 font-bold text-primary">تخوم</span></span>
                <nav class="flex flex-wrap gap-x-7 gap-y-3 text-14">
                    <a href="{{ route('login') }}" class="text-body">دخول</a>
                    <a href="{{ route('signup') }}" class="text-body">إنشاء حساب</a>
                    @if ($support['email'])
                        <a href="mailto:{{ $support['email'] }}" class="text-body">تواصل معنا</a>
                    @elseif ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="text-body">تواصل معنا</a>
                    @endif
                </nav>
            </div>
            <div class="flex flex-wrap justify-between gap-3 border-t border-divider pt-4.5 text-13 text-muted">
                <span>© {{ now()->year }} {{ $seller['name'] }}. جميع الحقوق محفوظة.</span>
                @if ($seller['cr_number'] || $seller['vat_number'])
                    <span>
                        @if ($seller['cr_number'])س.ت {{ $seller['cr_number'] }}@endif
                        @if ($seller['cr_number'] && $seller['vat_number']) · @endif
                        @if ($seller['vat_number'])الرقم الضريبي {{ $seller['vat_number'] }}@endif
                    </span>
                @endif
            </div>
        </div>
    </footer>

    @livewireScriptConfig
</body>
</html>
