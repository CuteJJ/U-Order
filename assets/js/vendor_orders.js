// assets/js/vendor_orders.js

// 状态管理
const pageState = { pending: 1, preparing: 1, ready: 1 };
let currentFilters = { category: 'all', pickup: 'all' };
let selectedIds = new Set(); // 記錄選中的 ID

// 等待頁面加載完成
document.addEventListener("DOMContentLoaded", () => {
    reloadAllColumns();

    // 綁定過濾器 (這裡會自動抓取 PHP 生成的 value)
    document.getElementById('filter-category').addEventListener('change', (e) => {
        currentFilters.category = e.target.value;
        reloadAllColumns();
    });
    
    document.getElementById('filter-pickup').addEventListener('change', (e) => {
        currentFilters.pickup = e.target.value;
        reloadAllColumns();
    });
});

/* ==========================================================
   下面的函數必須放在外面，HTML onclick 才能調用到！
   ========================================================== */

// 1. 單個按鈕點擊 (Cook / Ready)
function updateItemStatusSingle(id, newStatus, event) {
    // 阻止冒泡：防止點按鈕時也選中了卡片
    if (event) event.stopPropagation();
    
    // 直接調用後端
    callUpdateApi([id], newStatus);
}

// 2. 批量按鈕點擊
function batchUpdate(newStatus) {
    if (selectedIds.size === 0) {
        alert("Please select items first.");
        return;
    }
    callUpdateApi(Array.from(selectedIds), newStatus);
}

// 3. 切換卡片選中狀態 (點擊卡片空白處觸發)
function toggleSelection(card) {
    const id = card.getAttribute('data-id');
    
    // 找到卡片裡的 checkbox
    const checkbox = card.querySelector('.batch-checkbox');

    if (selectedIds.has(id)) {
        selectedIds.delete(id);
        card.classList.remove('selected');
        if(checkbox) checkbox.checked = false;
    } else {
        selectedIds.add(id);
        card.classList.add('selected');
        if(checkbox) checkbox.checked = true;
    }

    updateBatchButtons();
}

// 更新按鈕狀態 (變灰/變亮)
function updateBatchButtons() {
    const hasSelection = selectedIds.size > 0;
    const btnPrepare = document.getElementById('batchPrepare');
    const btnReady = document.getElementById('batchReady');

    if(btnPrepare) btnPrepare.disabled = !hasSelection;
    if(btnReady) btnReady.disabled = !hasSelection;
}

// 統一調用後端 API
function callUpdateApi(ids, status) {
    const formData = new URLSearchParams();
    formData.append('status', status);
    ids.forEach(id => formData.append('item_ids[]', id));

    // 按鈕變成 loading 狀態防止重復點擊
    document.body.style.cursor = 'wait';

    fetch('vendor_batch_update_items.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
        document.body.style.cursor = 'default';
        if (data.success) {
            selectedIds.clear();
            updateBatchButtons();
            reloadAllColumns(); // 刷新頁面看結果
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => {
        document.body.style.cursor = 'default';
        alert('Network Error');
    });
}

// 加載每一列
function fetchColumn(status) {
    const page = pageState[status];
    const params = new URLSearchParams({
        status: status,
        page: page,
        category: currentFilters.category,
        pickup: currentFilters.pickup
    });

    const listContainer = document.getElementById(`list-${status}`);
    const loadMoreBtn = document.getElementById(`btn-more-${status}`);

    // 如果是第一頁，顯示 loading 骨架
    if (page === 1) {
        listContainer.innerHTML = '<div class="spinner"></div>';
        loadMoreBtn.style.display = 'none';
    }

    fetch(`vendor_fetch_orders.php?${params.toString()}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (page === 1) {
                    listContainer.innerHTML = data.html;
                } else {
                    listContainer.insertAdjacentHTML('beforeend', data.html);
                }
                
                // 顯示/隱藏 Load More
                loadMoreBtn.style.display = data.hasMore ? 'block' : 'none';
                loadMoreBtn.innerText = 'Show More...';
                
                // 更新數字
                updateCount(status);
            } else {
                listContainer.innerHTML = '<p class="text-error">Error loading data</p>';
            }
        });
}

function loadMore(status) {
    pageState[status]++;
    fetchColumn(status);
}

function reloadAllColumns() {
    pageState.pending = 1; 
    pageState.preparing = 1; 
    pageState.ready = 1;
    fetchColumn('pending');
    fetchColumn('preparing');
    fetchColumn('ready');
}

function updateCount(status) {
    const count = document.querySelectorAll(`#list-${status} .item-card`).length;
    const el = document.getElementById(`count-${status}`);
    if(el) el.innerText = count;
}