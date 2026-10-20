@extends('layouts.dashboard')

@section('title', 'Eligibility Questions')
@section('header', 'Eligibility Questions for ' . $tender->tender_number)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600">
            <h3 class="text-lg font-semibold text-white">
                <i class="fas fa-question-circle mr-2"></i>Define Eligibility Criteria
            </h3>
        </div>

        <div class="p-6">
            <form id="questionsForm" method="POST" action="{{ route((auth()->user()->hasRole('super-admin') ? 'super-admin' : 'admin') . '.tenders.eligibility.store', $tender->id) }}">
                @csrf
                <div id="questionsContainer">
                    <!-- Questions added dynamically -->
                </div>

                <button type="button" onclick="addQuestion()"
                    class="mt-4 mb-6 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                    <i class="fas fa-plus mr-2"></i>Add Question
                </button>

                <div class="flex justify-end space-x-3">
                    <a href="{{ route('admin.tenders.show', $tender->id) }}"
                       class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</a>
                    <button type="submit"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                        <i class="fas fa-save mr-2"></i>Save Questions
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let questionCount = 0;
let optCounters = {};

function addQuestion(data) {
    data = data || null;
    const container = document.getElementById('questionsContainer');
    const qi = questionCount;
    optCounters[qi] = 0;

    const ft = data?.field_type || 'text';
    const isOpt = ['select','radio','checkbox'].includes(ft);

    const div = document.createElement('div');
    div.className = 'question-item bg-gray-50 rounded-lg p-4 mb-4 border border-gray-200';
    div.setAttribute('data-qi', qi);

    div.innerHTML = `
        <div class="flex justify-between items-center mb-3">
            <h4 class="font-semibold text-gray-800">Question ${qi + 1}</h4>
            <button type="button" onclick="removeQuestion(this)" class="text-red-600 hover:text-red-800">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Question *</label>
                <input type="text" name="questions[${qi}][question]"
                       value="${escHtml(data?.question || '')}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                <textarea name="questions[${qi}][description]" rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg">${escHtml(data?.description || '')}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Field Type *</label>
                    <select name="questions[${qi}][field_type]" onchange="onTypeChange(${qi})" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="text"     ${ft==='text'     ? 'selected':''}>Short Text</option>
                        <option value="number"   ${ft==='number'   ? 'selected':''}>Number</option>
                        <option value="select"   ${ft==='select'   ? 'selected':''}>Dropdown (Select)</option>
                        <option value="radio"    ${ft==='radio'    ? 'selected':''}>Radio (Single Choice)</option>
                        <option value="checkbox" ${ft==='checkbox' ? 'selected':''}>Checkbox (Multi Choice)</option>
                        <option value="file"     ${ft==='file'     ? 'selected':''}>File Upload</option>
                    </select>
                </div>
                <div class="flex flex-col gap-2 justify-center">
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer">
                        <input type="checkbox" name="questions[${qi}][is_required]" value="1"
                               ${(data?.is_required !== false) ? 'checked' : ''}
                               class="accent-purple-600"> Required
                    </label>
                    <label id="deLabel_${qi}" class="flex items-center gap-2 text-sm font-medium cursor-pointer ${isOpt || ['text','number'].includes(ft) ? 'text-purple-700' : 'text-gray-400'}">
                        <input type="checkbox" id="de_${qi}"
                               name="questions[${qi}][determines_eligibility]" value="1"
                               ${data?.determines_eligibility ? 'checked' : ''}
                               onchange="onDeChange(${qi})"
                               class="accent-red-500">
                        Determines Eligibility
                    </label>
                </div>
            </div>

            {{-- Options section --}}
            <div id="optSec_${qi}" style="display:${isOpt ? 'block' : 'none'}">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <label id="optHint_${qi}" class="block text-sm font-semibold text-gray-700">
                        ${ft === 'checkbox' ? 'Options — tick "Required" = vendor MUST check it' : 'Options — tick "Eligible" = selecting this passes eligibility'}
                    </label>
                </div>
                <div id="optList_${qi}" style="display:flex;flex-direction:column;gap:6px;margin-bottom:8px;"></div>
                <button type="button" onclick="addOpt(${qi})"
                    style="font-size:12px;padding:6px 14px;border:1.5px dashed #cbd5e1;border-radius:8px;background:transparent;color:#64748b;cursor:pointer;width:100%;display:flex;align-items:center;justify-content:center;gap:6px;">
                    <i class="fas fa-plus-circle"></i> Add Option
                </button>
            </div>

            {{-- Condition section (text/number + determines_eligibility) --}}
            <div id="condSec_${qi}" style="display:none;" class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <label class="block text-sm font-semibold text-purple-700 mb-2">Eligibility Condition</label>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <select id="condOp_${qi}" name="questions[${qi}][eligibility_condition][operator]"
                            onchange="onCondOpChange(${qi})"
                            style="border:1.5px solid #e2e8f0;border-radius:8px;padding:7px 12px;font-size:13px;color:#374151;background:#fff;min-width:160px;">
                        <option value="any">Any non-empty answer</option>
                        <option value="equals">Equals</option>
                        <option value="contains">Contains (text)</option>
                        <option value="gte">Greater than or equal (≥)</option>
                        <option value="lte">Less than or equal (≤)</option>
                        <option value="between">Between</option>
                    </select>
                    <input type="text" id="condVal_${qi}" name="questions[${qi}][eligibility_condition][value]"
                        placeholder="value"
                        style="display:none;border:1.5px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:13px;width:100px;">
                    <span id="condAnd_${qi}" style="display:none;font-size:13px;color:#64748b;">and</span>
                    <input type="text" id="condVal2_${qi}" name="questions[${qi}][eligibility_condition][value2]"
                        placeholder="value"
                        style="display:none;border:1.5px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:13px;width:100px;">
                </div>
            </div>

            {{-- File size --}}
            <div id="fileSec_${qi}" style="display:${ft === 'file' ? 'block' : 'none'}">
                <label class="block text-sm font-medium text-gray-700 mb-1">Max File Size (KB)</label>
                <input type="number" name="questions[${qi}][max_size]"
                       value="${data?.max_size || 5120}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
        </div>
    `;

    container.appendChild(div);

    // Pre-populate options if editing existing data
    if (data?.options && Array.isArray(data.options) && data.options.length) {
        data.options.forEach(opt => {
            const lbl = typeof opt === 'string' ? opt : (opt.label || '');
            const elig = typeof opt === 'object' ? !!(opt.eligible) : false;
            addOptRow(qi, lbl, elig);
        });
    } else if (isOpt) {
        addOptRow(qi, '', false);
    }

    // Pre-populate condition
    if (data?.eligibility_condition) {
        const c = data.eligibility_condition;
        const sel = document.getElementById(`condOp_${qi}`);
        if (sel) sel.value = c.operator || 'any';
        const v = document.getElementById(`condVal_${qi}`);
        if (v) v.value = c.value || '';
        const v2 = document.getElementById(`condVal2_${qi}`);
        if (v2) v2.value = c.value2 || '';
        onCondOpChange(qi);
    }

    // Show condition section if determines_eligibility and text/number
    if (data?.determines_eligibility && ['text','number'].includes(ft)) {
        const cs = document.getElementById(`condSec_${qi}`);
        if (cs) cs.style.display = 'block';
    }

    questionCount++;
}

function addOpt(qi) {
    addOptRow(qi, '', false);
}

function addOptRow(qi, labelVal, eligVal) {
    const ft = document.querySelector(`select[name="questions[${qi}][field_type]"]`)?.value || 'select';
    const eligLabel = ft === 'checkbox' ? 'Required' : 'Eligible';
    const list = document.getElementById(`optList_${qi}`);
    if (!list) return;
    const oi = optCounters[qi]++;
    const id = `opt_${qi}_${oi}`;
    const row = document.createElement('div');
    row.id = id;
    row.style.cssText = 'display:flex;align-items:center;gap:8px;padding:8px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;';
    row.innerHTML = `
        <i class="fas fa-grip-vertical" style="color:#cbd5e1;font-size:12px;"></i>
        <input type="text" name="questions[${qi}][options][${oi}][label]" value="${escHtml(labelVal)}"
               placeholder="Option text…"
               style="flex:1;border:1.5px solid #e2e8f0;border-radius:6px;padding:6px 10px;font-size:13px;outline:none;">
        <label style="display:flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:#475569;white-space:nowrap;cursor:pointer;">
            <input type="checkbox" name="questions[${qi}][options][${oi}][eligible]" value="1"
                   ${eligVal ? 'checked' : ''}
                   style="accent-color:#16a34a;width:14px;height:14px;">
            ${eligLabel}
        </label>
        <button type="button" onclick="document.getElementById('${id}').remove()"
                style="width:24px;height:24px;border-radius:50%;background:#fee2e2;border:none;color:#dc2626;cursor:pointer;font-size:11px;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-times"></i>
        </button>
    `;
    list.appendChild(row);
}

function onTypeChange(qi) {
    const sel = document.querySelector(`select[name="questions[${qi}][field_type]"]`);
    const ft = sel ? sel.value : 'text';
    const isOpt = ['select','radio','checkbox'].includes(ft);
    const isTextNum = ['text','number'].includes(ft);
    const deChk = document.getElementById(`de_${qi}`);

    document.getElementById(`optSec_${qi}`).style.display  = isOpt     ? 'block' : 'none';
    document.getElementById(`fileSec_${qi}`).style.display = ft==='file' ? 'block' : 'none';

    const hint = document.getElementById(`optHint_${qi}`);
    if (hint) {
        hint.textContent = ft === 'checkbox'
            ? 'Options — tick "Required" = vendor MUST check it'
            : 'Options — tick "Eligible" = selecting this passes eligibility';
    }

    if (deChk) {
        const condSec = document.getElementById(`condSec_${qi}`);
        if (condSec) condSec.style.display = (isTextNum && deChk.checked) ? 'block' : 'none';

        if (!isOpt && !isTextNum) {
            deChk.checked = false;
            if (condSec) condSec.style.display = 'none';
        }
    }

    const list = document.getElementById(`optList_${qi}`);
    if (isOpt && list && list.children.length === 0) {
        addOptRow(qi, '', false);
    }

    // Update numeric-only operators
    if (isTextNum) updateCondOps(qi, ft);
}

function onDeChange(qi) {
    const deChk = document.getElementById(`de_${qi}`);
    const sel = document.querySelector(`select[name="questions[${qi}][field_type]"]`);
    const ft = sel ? sel.value : 'text';
    const isTextNum = ['text','number'].includes(ft);
    const condSec = document.getElementById(`condSec_${qi}`);
    if (condSec) condSec.style.display = (isTextNum && deChk && deChk.checked) ? 'block' : 'none';
}

function updateCondOps(qi, type) {
    const sel = document.getElementById(`condOp_${qi}`);
    if (!sel) return;
    const numOnly = ['gte','lte','between'];
    Array.from(sel.options).forEach(opt => {
        if (type === 'text' && numOnly.includes(opt.value)) {
            opt.style.display = 'none';
            if (opt.selected) sel.value = 'any';
        } else {
            opt.style.display = '';
        }
    });
    onCondOpChange(qi);
}

function onCondOpChange(qi) {
    const op = document.getElementById(`condOp_${qi}`)?.value;
    const show = op && op !== 'any';
    const show2 = op === 'between';
    const v  = document.getElementById(`condVal_${qi}`);
    const a  = document.getElementById(`condAnd_${qi}`);
    const v2 = document.getElementById(`condVal2_${qi}`);
    if (v)  v.style.display  = show  ? '' : 'none';
    if (a)  a.style.display  = show2 ? '' : 'none';
    if (v2) v2.style.display = show2 ? '' : 'none';
}

function removeQuestion(btn) {
    btn.closest('.question-item').remove();
}

function escHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// Pre-populate with the tender's existing eligibility questions, if any;
// otherwise start with one blank question for a first-time setup.
const existingQuestions = @json($existingQuestionsForJs);

if (existingQuestions.length) {
    existingQuestions.forEach(q => addQuestion(q));
} else {
    addQuestion();
}
</script>
@endsection
