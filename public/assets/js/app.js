function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const wrapper = document.createElement('div');
    const variant = type === 'error' ? 'danger' : type;
    wrapper.className = `toast align-items-center text-bg-${variant}`;
    wrapper.setAttribute('role', 'alert');
    wrapper.setAttribute('aria-live', 'assertive');
    wrapper.setAttribute('aria-atomic', 'true');
    wrapper.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>`;
    container.appendChild(wrapper);
    const toast = new bootstrap.Toast(wrapper, { delay: 3000 });
    toast.show();
    wrapper.addEventListener('hidden.bs.toast', () => wrapper.remove());
}

function showLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.classList.remove('d-none');
}

function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.classList.add('d-none');
}
