{{-- resources/views/SuperAdmin/translate/index.blade.php --}}
@extends('layouts.superadmin')

@section('content')
 <div class="p-3 sm:p-4 md:p-6 w-full max-w-full sm:mx-0 min-w-0" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
  <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 sm:gap-3 mb-6 min-w-0">
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 break-words">@tr('Translations')</h1>
      <div class="flex items-center gap-2 shrink-0">
          <a href="{{ route('l10n.translate.index',['group'=>$group]) }}"
              class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-2 rounded text-sm sm:text-base">
            @tr('Reset')
          </a>
      </div>
  </div>

  @php
      use App\Support\Flash as FlashBag;

      // اسحب وامسح فورًا
      $messages = (array) session()->pull('flashes', []);

      // دعم مفاتيح قديمة: ok, success, error, status
      foreach (['ok','success','error','status'] as $legacy) {
          if (session()->has($legacy)) {
              $messages[] = FlashBag::normalize(
                  session()->pull($legacy),
                  $legacy === 'error' ? 'error' : 'success'
              );
          }
      }

      // إزالة التكرارات حسب النوع+العنوان+التفصيل
      $seen = [];
      $messages = array_values(
          array_filter($messages, function ($m) use (&$seen) {
              $k = ($m['type'] ?? '') . '|' . ($m['title'] ?? '') . '|' . ($m['detail'] ?? '');
              if (isset($seen[$k])) return false;
              $seen[$k] = true;
              return true;
          }),
      );

      $side = app()->isLocale('ar') ? 'left-3' : 'right-3';
  @endphp

  @if ($messages)
      <div class="fixed {{ $side }} top-3 z-50 space-y-2 w-[90vw] max-w-80">
          @foreach ($messages as $i => $m)
              @php
                  $bg = [
                      'success' => 'bg-indigo-700', // كان أخضر
                      'error'   => 'bg-red-600',
                      'info'    => 'bg-slate-700',
                      'warning' => 'bg-amber-600',
                  ][$m['type']] ?? 'bg-slate-700';

                  $id  = 'flash-' . $i;
                  $dur = (int) ($m['duration'] ?? 3000);
              @endphp

              <div id="{{ $id }}" class="{{ $bg }} text-white rounded-lg shadow-md p-3 sm:p-4 transition-opacity duration-300">
                  @if (!empty($m['title']))
                      <p class="font-semibold">{{ $m['title'] }}</p>
                  @endif
                  @if (!empty($m['detail']))
                      <p class="text-xs mt-1 opacity-90">{{ $m['detail'] }}</p>
                  @endif
              </div>

              <script>
                  (function () {
                      const el = document.getElementById(@json($id));
                      if (!el) return;
                      setTimeout(() => {
                          el.style.opacity = '0';
                          setTimeout(() => el.remove(), 300);
                      }, {{ $dur }});
                  })();
              </script>
          @endforeach
      </div>
  @endif


  {{-- Toolbar --}}
  <div class="bg-white p-4 rounded-lg shadow-md mb-4">
    <form method="get" class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-3 w-full items-start" id="serverToolbar">
     <input name="q" id="q" value="{{ $q }}" placeholder="@tr('Search key or text')" class="border px-3 py-2 rounded w-full sm:col-span-5 md:col-span-6 focus:ring focus:ring-blue-200 min-w-0" autocomplete="off">

      <select name="group" class="border px-2 py-2 rounded focus:ring w-full sm:col-span-2 md:col-span-2">
        <option value="ui" @selected($group==='ui')>ui</option>
        @foreach($groups as $g) @continue($g==='ui')
          <option value="{{ $g }}" @selected($group===$g)>{{ $g }}</option>
        @endforeach
      </select>
      <select name="per"   class="border px-2 py-2 rounded focus:ring w-full sm:col-span-2 md:col-span-2">
        @foreach([20,50,100] as $n) <option @selected($perPage==$n)>{{ $n }}</option> @endforeach
      </select>

       <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full sm:col-span-3 md:col-span-2">@tr('Search')</button>

      @php
        $missingEn = $items->filter(fn($i)=>empty($i->text['en'] ?? null))->count();
        $missingAr = $items->filter(fn($i)=>empty($i->text['ar'] ?? null))->count();
      @endphp
      <div class="w-full sm:col-span-12 flex flex-wrap items-center gap-2 text-sm sm:justify-end">
        <button type="button" id="fEn"
          class="bg-blue-100 text-blue-800 px-3 py-1 rounded-md hover:bg-blue-200">
          EN @tr('missing'): <span id="countEn" class="inline-block px-2 rounded bg-blue-200 ltr:ml-1 rtl:mr-1">{{ $missingEn }}</span>
        </button>
        <button type="button" id="fAr"
          class="bg-blue-100 text-blue-800 px-3 py-1 rounded-md hover:bg-blue-200">
          AR @tr('missing'): <span id="countAr" class="inline-block px-2 rounded bg-blue-200 ltr:ml-1 rtl:mr-1">{{ $missingAr }}</span>

        </button>
        <button type="button" id="fAll"
          class="bg-gray-100 text-gray-800 px-3 py-1 rounded-md hover:bg-gray-200">
          @tr('All')
        </button>
      </div>
    </form>
  </div>

  {{-- Table --}}
  <div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-xl font-semibold mb-4">@tr('Current Translations')</h2>

    <form method="post" action="{{ route('l10n.translate.bulk') }}" id="bulkForm" class="space-y-2">
      @csrf

     <div class="overflow-x-auto rounded border -mx-3 sm:mx-0 max-w-full">
       <table class="min-w-[720px] sm:min-w-0 w-full text-sm ltr:text-left rtl:text-right">
          <thead class="bg-gray-50 sticky top-0 z-10 text-xs sm:text-sm">
            <tr>
              <th class="p-2 w-2/5 ltr:text-left rtl:text-right text-gray-600">EN</th>
              <th class="p-2 w-2/5 ltr:text-left rtl:text-right text-gray-600">AR</th>
              <th class="p-2 w-24 text-center text-gray-600">@tr('Actions')</th>
            </tr>
          </thead>
          <tbody id="rows" class="divide-y divide-gray-200">
          @foreach($items as $it)
            @php
              $enEmpty = empty($it->text['en'] ?? null);
              $arEmpty = empty($it->text['ar'] ?? null);
            @endphp
            <tr class="hover:bg-gray-50"
                data-id="{{ $it->id }}"
                data-key="{{ $it->key }}"
                data-missing-en="{{ $enEmpty ? '1':'0' }}"
                data-missing-ar="{{ $arEmpty ? '1':'0' }}">

              <td class="p-2 cell-en">
              <input name="values_en[{{ $it->id }}]" value="{{ $it->text['en'] ?? '' }}"
                       class="border px-2 py-1 rounded w-full focus:ring text-left
                       {{ $enEmpty ? 'bg-amber-50 border-amber-300' : '' }}">
              </td>

              <td class="p-2 cell-ar">
              <input name="values_ar[{{ $it->id }}]" value="{{ $it->text['ar'] ?? '' }}"
                       class="border px-2 py-1 rounded w-full focus:ring text-right
                       {{ $arEmpty ? 'bg-amber-50 border-amber-300' : '' }}">
              </td>

              <td class="p-2 text-center">
                <button type="button"
                        class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-md hover:bg-blue-200 save-row">
                  @tr('Save')
                </button>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>

<div class="flex flex-col gap-3 pt-3 min-w-0 w-full sm:flex-row sm:items-center sm:justify-between">
    <div class="text-xs text-gray-600 order-1 sm:order-none">
        صفحة {{ $items->currentPage() }} من {{ $items->lastPage() }}
    </div>
    <div class="w-full overflow-x-auto -mx-3 sm:mx-0 order-2 sm:order-none">
        <div class="inline-block min-w-max px-4">{{ $items->links() }}</div>
    </div>
    <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full sm:w-auto order-3 sm:order-none">
        @tr('Save All')
    </button>
</div>
    </form>
  </div>
</div>

<script>
(function(){
  // Single row save
  document.querySelectorAll('.save-row').forEach(btn => {
    btn.addEventListener('click', () => {
      const tr = btn.closest('tr');
      const id = tr.dataset.id;
      const en = tr.querySelector(`[name="values_en[${id}]"]`)?.value ?? '';
      const ar = tr.querySelector(`[name="values_ar[${id}]"]`)?.value ?? '';

      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '{{ route('l10n.translate.update', 0) }}'.replace('/0','/'+id);
      form.innerHTML = `
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="en" value="">
        <input type="hidden" name="ar" value="">
      `;
      form.querySelector('input[name="en"]').value = en;
      form.querySelector('input[name="ar"]').value = ar;
      document.body.appendChild(form);
      form.submit();
    });
  });

  // Client filtering
  const rows = Array.from(document.querySelectorAll('#rows tr'));
  const q = document.getElementById('q');
  const countEn = document.getElementById('countEn');
  const countAr = document.getElementById('countAr');
  let mode = 'all';

  const norm = s => (s || '').toLowerCase();

  function apply() {
    const needle = norm(q.value);
    let enMissing = 0, arMissing = 0;

    rows.forEach(r=>{
      const key = r.dataset.key;
      const en = r.querySelector(`[name="values_en[${r.dataset.id}]"]`).value;
      const ar = r.querySelector(`[name="values_ar[${r.dataset.id}]"]`).value;

      const match = !needle || [key,en,ar].some(v => norm(v).includes(needle));
      const isEnMissing = (en.trim()==='');
      const isArMissing = (ar.trim()==='');

      let show = match;
      if(mode==='en') show = show && isEnMissing;
      if(mode==='ar') show = show && isArMissing;

      r.style.display = show ? '' : 'none';

      if(isEnMissing) enMissing++;
      if(isArMissing) arMissing++;
    });

    countEn.textContent = enMissing;
    countAr.textContent = arMissing;
  }

  q.addEventListener('input', ()=>{ clearTimeout(window.__t); window.__t=setTimeout(apply,130); });
  document.getElementById('fEn').addEventListener('click', ()=>{ mode='en'; apply(); });
  document.getElementById('fAr').addEventListener('click', ()=>{ mode='ar'; apply(); });
  document.getElementById('fAll').addEventListener('click', ()=>{ mode='all'; apply(); });

  apply();
})();
</script>
@endsection
