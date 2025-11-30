// 选中的 itemId 集合（给 batch 用）
let selectedItems = new Set();

document.addEventListener("DOMContentLoaded", () => {
    const batchPrepareBtn = document.getElementById("batchPrepare");
    const batchReadyBtn   = document.getElementById("batchReady");

    /* 1. 点击整张卡片 -> 切换选中状态 */
    document.querySelectorAll(".order-item-wrapper .item-card").forEach(card => {
        card.addEventListener("click", (e) => {
            // 如果点到的是按钮，就不要触发选中切换
            if (e.target.tagName === "BUTTON") {
                return;
            }

            const id = card.dataset.id;
            const isSelected = card.classList.toggle("selected");

            if (isSelected) {
                selectedItems.add(id);
            } else {
                selectedItems.delete(id);
            }

            updateBatchButtons(batchPrepareBtn, batchReadyBtn);
        });
    });

    /* 2. 批量按钮 click */
    if (batchPrepareBtn) {
        batchPrepareBtn.addEventListener("click", () => {
            if (selectedItems.size === 0) return;
            ajaxBatchUpdate(Array.from(selectedItems), "preparing", () => {
                location.reload();
            });
        });
    }

    if (batchReadyBtn) {
        batchReadyBtn.addEventListener("click", () => {
            if (selectedItems.size === 0) return;
            ajaxBatchUpdate(Array.from(selectedItems), "ready", () => {
                location.reload();
            });
        });
    }
});

/* 更新批量按钮 disabled 状态 */
function updateBatchButtons(batchPrepareBtn, batchReadyBtn) {
    const hasSelection = selectedItems.size > 0;
    if (batchPrepareBtn) batchPrepareBtn.disabled = !hasSelection;
    if (batchReadyBtn)   batchReadyBtn.disabled = !hasSelection;
}

/* ============ 单个 item 的按钮调用这个 ============ */
function updateItemStatusSingle(itemId, newStatus, event) {
    if (event) event.stopPropagation(); // 防止点按钮时顺便选中/取消卡片

    const formData = new URLSearchParams();
    formData.append("order_item_id", itemId);
    formData.append("status", newStatus);

    fetch("../pages/vendor_update_orderitem_status.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.success) {
            location.reload();
        } else {
            alert(data && data.message ? data.message : "Update failed");
        }
    })
    .catch(() => {
        alert("Network error");
    });
}

/* ============ 批量更新（调用你之前的 vendor_batch_update_items.php）=========== */
function ajaxBatchUpdate(itemIds, newStatus, onSuccess) {
    const formData = new URLSearchParams();
    formData.append("status", newStatus);
    itemIds.forEach(id => formData.append("item_ids[]", id));

    fetch("../pages/vendor_batch_update_items.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.success) {
            if (typeof onSuccess === "function") onSuccess();
        } else {
            alert(data && data.message ? data.message : "Batch update failed");
        }
    })
    .catch(() => {
        alert("Network error");
    });
}
