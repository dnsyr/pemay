// Handler untuk tab switching
document.addEventListener('DOMContentLoaded', function() {
    // Handle tab switching
    const tabs = document.querySelectorAll('input[name="my_tabs_2"]');
    tabs.forEach(tab => {
        tab.addEventListener('change', function() {
            if (this.checked) {
                window.location.href = `?tab=${this.value}`;
            }
        });
    });

    // Handle drawer closing on success message
    if (document.querySelector('.alert-success')) {
        document.getElementById('my-drawer').checked = false;
    }
}); 