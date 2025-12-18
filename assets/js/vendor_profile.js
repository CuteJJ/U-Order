document.addEventListener("DOMContentLoaded", () => {
    // 全局通知元素
    const globalNotif = document.getElementById("vp-global-notif");

    // 顯示全局通知
    function showGlobal(message, type) {
        if (!globalNotif) return;
        globalNotif.textContent = message;
        // 切換 class (移除舊的，加上新的)
        globalNotif.className = "vp-global-notif" + (type === 'success' ? '' : ' error');
        globalNotif.style.display = 'block';

        // 3秒後消失
        setTimeout(() => {
            globalNotif.style.display = 'none';
        }, 3000);
    }

    // 設定欄位錯誤訊息
    function setFieldError(container, msg) {
        // 在该行内查找 .vp-error 容器
        const el = container.querySelector(".vp-error");
        if (el) {
            el.textContent = msg || "";
            el.style.display = msg ? 'block' : 'none';
        }
    }

    // ============================================
    // 1. Toggle Password Logic (图标切换)
    // ============================================
    document.querySelectorAll(".vp-toggle-pass").forEach(icon => {
        icon.addEventListener("click", () => {
            // 找到前面的 input (它应该是图标的前一个兄弟元素)
            const input = icon.previousElementSibling;
            
            if (input && (input.type === "password" || input.type === "text")) {
                if (input.type === "password") {
                    // 变成明文
                    input.type = "text";
                    // 图标变成 "闭眼/斜杠眼"
                    icon.classList.remove("fa-eye");
                    icon.classList.add("fa-eye-slash");
                } else {
                    // 变回密码
                    input.type = "password";
                    // 图标变回 "睁眼"
                    icon.classList.remove("fa-eye-slash");
                    icon.classList.add("fa-eye");
                }
            }
        });
    });

    // ============================================
    // 2. UI 切换逻辑 (Edit / Cancel)
    // ============================================
    // 綁定所有 Edit 按鈕
    document.querySelectorAll(".vp-edit-trigger").forEach(btn => {
        btn.addEventListener("click", () => {
            const row = btn.closest(".vp-edit-row");
            const displayMode = row.querySelector(".vp-display-mode");
            const editMode = row.querySelector(".vp-edit-mode");

            if (displayMode && editMode) {
                displayMode.style.display = "none";
                editMode.style.display = "block";
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
                editMode.style.display = "none";
                displayMode.style.display = ""; // 恢復 CSS 的 display (flex 或 block)
                
                // 清除錯誤
                if (errorDiv) {
                    errorDiv.style.display = 'none';
                    errorDiv.textContent = '';
                }

                // === 重置所有密码框和图标 ===
                const passInputs = editMode.querySelectorAll("input[type='text'], input[type='password']");
                const icons = editMode.querySelectorAll(".vp-toggle-pass");
                
                // 把输入框变回圆点 (type="password")
                passInputs.forEach(inp => {
                    // 只针对名字里包含 password 的输入框，避免误伤 phone
                    if (inp.name.includes('password')) inp.type = "password";
                });
                
                // 把图标变回 "睁眼" (fa-eye)
                icons.forEach(icon => {
                    icon.classList.remove("fa-eye-slash");
                    icon.classList.add("fa-eye");
                });
            }
        });
    });

    // ============================================
    // 3. Save Logic (Validation & AJAX)
    // ============================================
    document.querySelectorAll(".vp-save-edit").forEach(btn => {
        btn.addEventListener("click", () => {
            const action = btn.dataset.action;
            const row = btn.closest(".vp-edit-row");
            
            // 清除之前的錯誤
            setFieldError(row, "");

            let payload = new FormData();
            payload.append("action", action);

            // --- Phone Update Logic ---
            if (action === "update_phone") {
                const input = row.querySelector("input[name='phone']");
                const value = (input.value || "").trim();

                if (!/^\d{10,11}$/.test(value)) {
                    setFieldError(row, "Phone must be 10–11 digits, numbers only.");
                    return;
                }
                payload.append("phone", value);
            }

            // --- Description Update Logic ---
            if (action === "update_description") {
                const ta = row.querySelector("textarea[name='description']");
                const value = (ta.value || "").trim();
                
                if (value.length > 500) {
                    setFieldError(row, "Description must be at most 500 characters.");
                    return;
                }
                payload.append("description", value);
            }

            // --- Password Update Logic (Updated) ---
            if (action === "update_password") {
                const oldPassInput = row.querySelector("input[name='old_password']");
                const newPassInput = row.querySelector("input[name='new_password']");
                
                const oldPassword = oldPassInput.value;
                const newPassword = newPassInput.value;

                if (!oldPassword || !newPassword) {
                    setFieldError(row, "Current and new password are required.");
                    return;
                }
                
                // === 验证 8-16 位 ===
                if (newPassword.length < 8 || newPassword.length > 16) {
                    setFieldError(row, "New password must be between 8 and 16 characters.");
                    return;
                }
                
                payload.append("old_password", oldPassword);
                payload.append("new_password", newPassword);
            }

            // --- 發送 AJAX 請求 ---
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

                // 成功後更新 UI 文字
                if (action === "update_phone") {
                    const disp = row.querySelector(".vp-value-text");
                    if (disp) disp.textContent = data.newValue || "";
                }
                
                if (action === "update_description") {
                    const disp = row.querySelector(".vp-value-text");
                    if (disp) {
                        const val = data.newValue || "";
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