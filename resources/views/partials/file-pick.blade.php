{{-- 파일 첨부 — 고른 파일 이름을 보여준다 (사업자등록증 등) --}}
@once
@push('scripts')
<script>
document.addEventListener('change', function (e) {
    var input = e.target;
    if (!input.matches('.file-pick input[type="file"]')) return;

    var box = input.closest('.file-pick');
    var label = box.querySelector('[data-file-name]');
    var file = input.files && input.files[0];

    if (!file) {
        box.classList.remove('has-file');
        label.textContent = label.getAttribute('data-empty') || '선택된 파일이 없습니다';
        return;
    }

    var size = file.size > 1048576
        ? (file.size / 1048576).toFixed(1) + 'MB'
        : Math.max(1, Math.round(file.size / 1024)) + 'KB';
    box.classList.add('has-file');
    label.textContent = file.name + ' · ' + size;
});
</script>
@endpush
@endonce
