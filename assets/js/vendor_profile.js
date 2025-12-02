// ../assets/vendor_profile.js

document.addEventListener("DOMContentLoaded", () => {
    const fields = document.querySelectorAll(".vp-field");
    const globalNotif = document.getElementById("vp-global-notif");

    function showGlobal(message, type) {
        if (!globalNotif) return;
        globalNotif.innerHTML = `
            <div class="vp-global-notif-inner ${
                type === "success" ? "vp-global-notif-success" : "vp-global-notif-error"
            }">
                <span>${message}</span>
            </div>
        `;
        setTimeout(() => {
            globalNotif.innerHTML = "";
        }, 4000);
    }

    function setFieldError(fieldName, msg) {
        const el = document.querySelector(`.vp-error[data-error-for="${fieldName}"]`);
        if (el) el.textContent = msg || "";
    }

    fields.forEach(field => {
        const editBtn = field.querySelector(".vp-edit-trigger");
        const cancelBtn = field.querySelector(".vp-cancel-edit");
        const saveBtn = field.querySelector(".vp-save-edit");

        if (editBtn) {
            editBtn.addEventListener("click", () => {
                // 關閉其他正在編輯的
                document.querySelectorAll(".vp-field.editing").forEach(f => {
                    if (f !== field) f.classList.remove("editing");
                });
                field.classList.add("editing");
                const input = field.querySelector(".vp-input, .vp-textarea");
                if (input) input.focus();
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener("click", () => {
                field.classList.remove("editing");
                const fieldName = field.dataset.field;
                setFieldError(fieldName, "");
            });
        }

        if (saveBtn) {
            saveBtn.addEventListener("click", () => {
                const action = saveBtn.dataset.action;
                const fieldName = field.dataset.field;

                setFieldError(fieldName, "");

                let payload = new FormData();
                payload.append("action", action);

                if (action === "update_phone") {
                    const input = field.querySelector("input[name='phone']");
                    const value = (input.value || "").trim();

                    if (!/^\d{10,11}$/.test(value)) {
                        setFieldError("phone", "Phone must be 10–11 digits, numbers only.");
                        return;
                    }
                    payload.append("phone", value);
                }

                if (action === "update_description") {
                    const ta = field.querySelector("textarea[name='description']");
                    const value = (ta.value || "").trim();
                    if (value.length > 500) {
                        setFieldError("description", "Description must be at most 500 characters.");
                        return;
                    }
                    payload.append("description", value);
                }

                if (action === "update_password") {
                    const oldPassword = field.querySelector("input[name='old_password']").value;
                    const newPassword = field.querySelector("input[name='new_password']").value;

                    if (!oldPassword || !newPassword) {
                        setFieldError("password", "Current and new password are required.");
                        return;
                    }
                    if (newPassword.length < 6) {
                        setFieldError("password", "New password must be at least 6 characters.");
                        return;
                    }
                    payload.append("old_password", oldPassword);
                    payload.append("new_password", newPassword);
                }

                fetch("../pages/vendor_profile_update.php", {
                    method: "POST",
                    body: payload
                })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.success) {
                            setFieldError(fieldName, data.message || "Update failed.");
                            showGlobal(data.message || "Update failed.", "error");
                            return;
                        }

                        // 成功後更新顯示區文字
                        if (action === "update_phone") {
                            const disp = document.querySelector("[data-display-for='phone']");
                            if (disp) disp.textContent = data.newValue || "";
                        }
                        if (action === "update_description") {
                            const disp = document.querySelector("[data-display-for='description']");
                            if (disp) {
                                const val = data.newValue || "";
                                disp.innerHTML = val ? val.replace(/\n/g, "<br>") : "<span class=\"vp-muted\">No description yet.</span>";
                            }
                        }
                        if (action === "update_password") {
                            // 清空輸入框
                            field.querySelector("input[name='old_password']").value = "";
                            field.querySelector("input[name='new_password']").value = "";
                        }

                        field.classList.remove("editing");
                        setFieldError(fieldName, "");
                        showGlobal(data.message || "Updated successfully.", "success");
                    })
                    .catch(() => {
                        setFieldError(fieldName, "Something went wrong. Please try again.");
                        showGlobal("Something went wrong. Please try again.", "error");
                    });
            });
        }
    });
});
