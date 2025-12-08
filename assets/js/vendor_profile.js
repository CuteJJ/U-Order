/* assets/js/vendor_profile.js */

document.addEventListener("DOMContentLoaded", () => {
    // 全局通知元素
    const globalNotif = document.getElementById("vp-global-notif");

    // 顯示全局通知
    function showGlobal(message, type) {
        if (!globalNotif) return;
        globalNotif.textContent = message;
        // 切換 class (移除舊的，加上新的)
        globalNotif.className = `vp-global-notif ${type === 'success' ? '' : 'error'}`;
        globalNotif.style.display = 'block';

        // 3秒後消失
        setTimeout(() => {
            globalNotif.style.display = 'none';
        }, 3000);
    }

    // 設定欄位錯誤訊息
    function setFieldError(container, msg) {
        const el = container.querySelector(".vp-error");
        if (el) {
            el.textContent = msg || "";
            el.style.display = msg ? 'block' : 'none';
        }
    }

    // 綁定所有 Edit 按鈕
    document.querySelectorAll(".vp-edit-trigger").forEach(btn => {
        btn.addEventListener("click", () => {
            // 找到最近的編輯列 (.vp-edit-row)
            const row = btn.closest(".vp-edit-row");
            const displayMode = row.querySelector(".vp-display-mode");
            const editMode = row.querySelector(".vp-edit-mode");

            if (displayMode && editMode) {
                // 切換顯示
                displayMode.style.display = "none";
                editMode.style.display = "block";
                
                // 自動 Focus 輸入框
                const input = editMode.querySelector("input, textarea");
                if (input) input.focus();
            }
        });
    });

    // 綁定所有 Cancel 按鈕
    document.querySelectorAll(".vp-cancel-edit").forEach(btn => {
        btn.addEventListener("click", () => {
            const row = btn.closest(".vp-edit-row");
            const displayMode = row.querySelector(".vp-display-mode");
            const editMode = row.querySelector(".vp-edit-mode");
            const errorDiv = row.querySelector(".vp-error");

            if (displayMode && editMode) {
                // 恢復顯示
                editMode.style.display = "none";
                // 對於 flex 佈局的 display-mode，我們恢復 flex；其他的恢復 block
                // 為了保險，我們讀取 CSS 或直接設為 flex (因為 CSS 裡有設定 display: flex)
                // 簡單起見，移除 style.display 讓 CSS 接管，或者是設為 flex
                displayMode.style.display = ""; // 清除 inline style，讓 CSS 恢復 flex
                
                // 清除錯誤
                if (errorDiv) {
                    errorDiv.style.display = 'none';
                    errorDiv.textContent = '';
                }
            }
        });
    });

    // 綁定所有 Save 按鈕
    document.querySelectorAll(".vp-save-edit").forEach(btn => {
        btn.addEventListener("click", () => {
            const action = btn.dataset.action;
            const section = btn.closest(".vp-section"); // 用來抓取 data-field
            const row = btn.closest(".vp-edit-row");
            
            // 清除之前的錯誤
            setFieldError(row, "");

            let payload = new FormData();
            payload.append("action", action);

            // ===========================
            // 1. Phone Update Logic
            // ===========================
            if (action === "update_phone") {
                const input = row.querySelector("input[name='phone']");
                const value = (input.value || "").trim();

                if (!/^\d{10,11}$/.test(value)) {
                    setFieldError(row, "Phone must be 10–11 digits, numbers only.");
                    return;
                }
                payload.append("phone", value);
            }

            // ===========================
            // 2. Description Update Logic
            // ===========================
            if (action === "update_description") {
                const ta = row.querySelector("textarea[name='description']");
                const value = (ta.value || "").trim();
                
                if (value.length > 500) {
                    setFieldError(row, "Description must be at most 500 characters.");
                    return;
                }
                payload.append("description", value);
            }

            // ===========================
            // 3. Password Update Logic
            // ===========================
            if (action === "update_password") {
                const oldPassInput = row.querySelector("input[name='old_password']");
                const newPassInput = row.querySelector("input[name='new_password']");
                
                const oldPassword = oldPassInput.value;
                const newPassword = newPassInput.value;

                if (!oldPassword || !newPassword) {
                    setFieldError(row, "Current and new password are required.");
                    return;
                }
                if (newPassword.length < 6) {
                    setFieldError(row, "New password must be at least 6 characters.");
                    return;
                }
                payload.append("old_password", oldPassword);
                payload.append("new_password", newPassword);
            }

            // ===========================
            // 發送 AJAX 請求
            // ===========================
            // 注意路徑：HTML 在 pages/vendor_profile.php，Action 也在 pages/vendor_profile_update.php
            // 所以直接寫檔名即可，不用 ../pages/
            fetch("vendor_profile_update.php", {
                method: "POST",
                body: payload
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    setFieldError(row, data.message || "Update failed.");
                    showGlobal(data.message || "Update failed.", "error");
                    return;
                }

                // 成功後更新 UI
                if (action === "update_phone") {
                    const disp = row.querySelector(".vp-value-text"); // 直接在 row 裡找
                    if (disp) disp.textContent = data.newValue || "";
                }
                
                if (action === "update_description") {
                    const disp = row.querySelector(".vp-value-text");
                    if (disp) {
                        const val = data.newValue || "";
                        // 將換行符號轉為 <br>
                        disp.innerHTML = val ? val.replace(/\n/g, "<br>") : '<span class="vp-placeholder">No description yet.</span>';
                    }
                }
                
                if (action === "update_password") {
                    // 清空密碼框
                    row.querySelector("input[name='old_password']").value = "";
                    row.querySelector("input[name='new_password']").value = "";
                }

                // 關閉編輯模式 (觸發 Cancel 按鈕的邏輯)
                const cancelBtn = row.querySelector(".vp-cancel-edit");
                if (cancelBtn) cancelBtn.click();

                // 顯示全域成功訊息
                showGlobal(data.message || "Updated successfully.", "success");
            })
            .catch(err => {
                console.error(err);
                setFieldError(row, "Server error. Please try again.");
                showGlobal("Server error. Please try again.", "error");
            });
        });
    });
});