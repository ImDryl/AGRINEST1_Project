// Order Table Pagination and Search
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('ordersTable');
    if (!table) return;

    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const pageLengthSelect = document.getElementById('pageLength');
    const searchInput = document.getElementById('tableSearch');
    const pageInfo = document.getElementById('pageInfo');
    const pagination = document.getElementById('pagination');

    let currentPage = 1;
    let pageLength = parseInt(pageLengthSelect.value) || 10;
    let filteredRows = rows;

    function filterRows() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        filteredRows = rows.filter(row => {
            const text = row.textContent.toLowerCase();
            return text.includes(searchTerm);
        });
        currentPage = 1;
        renderTable();
    }

    function renderTable() {
        const start = (currentPage - 1) * pageLength;
        const end = start + pageLength;
        const pageRows = filteredRows.slice(start, end);

        // Hide all rows
        rows.forEach(row => row.style.display = 'none');

        // Show page rows
        pageRows.forEach(row => row.style.display = '');

        // Update page info
        const total = filteredRows.length;
        const startNum = total === 0 ? 0 : start + 1;
        const endNum = Math.min(end, total);
        pageInfo.textContent = `Showing ${startNum} to ${endNum} of ${total} entries`;

        // Render pagination
        renderPagination();
    }

    function renderPagination() {
        const totalPages = Math.ceil(filteredRows.length / pageLength);
        pagination.innerHTML = '';

        if (totalPages <= 1) return;

        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.textContent = 'Previous';
        prevBtn.className = 'page-btn';
        prevBtn.disabled = currentPage === 1;
        prevBtn.onclick = () => {
            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        };
        pagination.appendChild(prevBtn);

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.className = 'page-btn';
                if (i === currentPage) {
                    btn.classList.add('active');
                }
                btn.onclick = () => {
                    currentPage = i;
                    renderTable();
                };
                pagination.appendChild(btn);
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                const span = document.createElement('span');
                span.textContent = '...';
                span.className = 'page-ellipsis';
                pagination.appendChild(span);
            }
        }

        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.textContent = 'Next';
        nextBtn.className = 'page-btn';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.onclick = () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderTable();
            }
        };
        pagination.appendChild(nextBtn);
    }

    // Event listeners
    pageLengthSelect.addEventListener('change', function() {
        pageLength = parseInt(this.value);
        currentPage = 1;
        renderTable();
    });

    searchInput.addEventListener('input', filterRows);

    // Initial render
    renderTable();
});

