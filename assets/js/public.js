// document.addEventListener('DOMContentLoaded', function () {

//     function evaluateRules(rules, values) {
//         // OR groups
//         return rules.some(group => {
//             // AND rules inside group
//             return group.rules.every(rule => {

//                 const fieldValue = values[rule.field] || '';

//                 //  IMPORTANT: empty value = condition fail
//                 if (fieldValue === '') {
//                     return false;
//                 }

//                 if (rule.operator === 'is') {
//                     return fieldValue === rule.value;
//                 }

//                 if (rule.operator === 'is_not') {
//                     return fieldValue !== rule.value;
//                 }

//                 return false;
//             });
//         });
//     }

//     function getFormValues(form) {
//         const values = {};
//         const inputs = form.querySelectorAll('input, select, textarea');

//         inputs.forEach(el => {
//             if (el.type === 'radio') {
//                 if (el.checked) values[el.name] = el.value;
//             } else {
//                 values[el.name] = el.value;
//             }
//         });

//         return values;
//     }

//     document.querySelectorAll('.pfb-form').forEach(form => {

//         const conditionalFields = form.querySelectorAll('.pfb-field[data-rules]');

//         function updateVisibility() {
//             const values = getFormValues(form);

//             conditionalFields.forEach(field => {
//                 const rules = JSON.parse(field.dataset.rules);

//                 const shouldShow = evaluateRules(rules, values);

//                 field.style.display = shouldShow ? '' : 'none';
//             });
//         }

//         // initial hide
//         updateVisibility();

//         // listen changes
//         form.addEventListener('change', updateVisibility);
//         form.addEventListener('input', updateVisibility);
//     });

// });

document.addEventListener('DOMContentLoaded', function () {

    function evaluateRules(rules, values) {
        return rules.some(group =>
            group.rules.every(rule => {
                const v = values[rule.field] || '';
                if (v === '') return false;
                if (rule.operator === 'is') return v === rule.value;
                if (rule.operator === 'is_not') return v !== rule.value;
                return false;
            })
        );
    }

    function getValues(form) {
        const values = {};
        form.querySelectorAll('input, select, textarea').forEach(el => {
            if (el.type === 'radio') {
                if (el.checked) values[el.name] = el.value;
            } else {
                values[el.name] = el.value;
            }
        });
        return values;
    }

    // function init(form) {

    //     // const conditionalFields = form.querySelectorAll('.pfb-field[data-rules]');
    //     const conditionalTargets = form.querySelectorAll('[data-rules]');
    //     if (!conditionalFields.length) return;

    //     function update() {
    //         const values = getValues(form);

    //         // conditionalFields.forEach(field => {
    //         //     const rules = JSON.parse(field.dataset.rules);
    //         //     const show = evaluateRules(rules, values);

    //         //     field.style.display = show ? '' : 'none';

    //         //     // required / disabled sync
    //         //     field.querySelectorAll('input,select,textarea').forEach(el => {
    //         //         if (!el.dataset.req) el.dataset.req = el.required ? '1' : '0';

    //         //         if (show) {
    //         //             el.disabled = false;
    //         //             el.required = el.dataset.req === '1';
    //         //         } else {
    //         //             el.disabled = true;
    //         //             el.required = false;
    //         //         }
    //         //     });
    //         // });
    //         conditionalTargets.forEach(el => {

    //             // child field skip if parent section hidden
    //             if (el.classList.contains('pfb-field')) {
    //                 const section = el.closest('.pfb-section-wrapper');
    //                 if (section && section.style.display === 'none') {
    //                     return;
    //                 }
    //             }

    //             const rules = JSON.parse(el.dataset.rules);
    //             const show = evaluateRules(rules, values);

    //             el.style.display = show ? '' : 'none';

    //             // only FIELD needs required/disabled sync
    //             if (el.classList.contains('pfb-field')) {
    //                 el.querySelectorAll('input,select,textarea').forEach(input => {
    //                     if (!input.dataset.req) {
    //                         input.dataset.req = input.required ? '1' : '0';
    //                     }

    //                     if (show) {
    //                         input.disabled = false;
    //                         input.required = input.dataset.req === '1';
    //                     } else {
    //                         input.disabled = true;
    //                         input.required = false;
    //                     }
    //                 });
    //             }

    //         });

    //     }

    //     update(); // THIS FIXES ADMIN EDIT ISSUE
    //     form.addEventListener('change', update);
    //     form.addEventListener('input', update);
    // }
    function init(form) {

        const conditionalTargets = form.querySelectorAll('[data-rules]');
        if (!conditionalTargets.length) return;

        function update() {
            const values = getValues(form);

            conditionalTargets.forEach(el => {

                if (el.classList.contains('pfb-field')) {
                    const section = el.closest('.pfb-section-wrapper');
                    if (section && section.style.display === 'none') return;
                }

                const rules = JSON.parse(el.dataset.rules);
                const show = evaluateRules(rules, values);

                el.style.display = show ? '' : 'none';

                if (el.classList.contains('pfb-field')) {
                    el.querySelectorAll('input,select,textarea').forEach(input => {
                        if (!input.dataset.req) {
                            input.dataset.req = input.required ? '1' : '0';
                        }

                        if (show) {
                            input.disabled = false;
                            input.required = input.dataset.req === '1';
                        } else {
                            input.disabled = true;
                            input.required = false;
                        }
                    });
                }
            });
        }

        update();
        form.addEventListener('change', update);
        form.addEventListener('input', update);
    }


    document
        .querySelectorAll('.pfb-form, .pfb-admin-form')
        .forEach(init);
});


 
/**
 * 
 * Auto Remove Form Submit Success Message
 * 
 */
setTimeout(() => {
    const msg = document.querySelector('.pfb-success');
    if (msg) msg.remove();
}, 4000); // 4 seconds

// Initialize on window load to ensure all elements are available
window.addEventListener('load', function() {
    document.querySelectorAll('.pfb-form, .pfb-admin-form').forEach(init);
});

// File || Image Remove Btn
$(document).on('change', 'input[name="pfb_remove_file[]"]', function() {
    if($(this).is(':checked')) {
        $(this).closest('.existing').css('opacity', '0.5');
    } else {
        $(this).closest('.existing').css('opacity', '1');
    }
});



// Remove error styles and messages on input
document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll('input, textarea, select').forEach(function (field) {

        field.addEventListener('input', function () {

            // Remove error styles
            field.classList.remove('error');
            field.classList.remove('invalid');
            field.style.borderColor = '';

            // Remove error message if exists
            const errorMsg = field.parentElement.querySelector('.pfb-error, .error-message');
            if (errorMsg) {
                errorMsg.remove();
            }

        });

    });

});

document.addEventListener("input", function (e) {
    if (e.target.closest('.pfb-field')) {
        const fieldWrap = e.target.closest('.pfb-field');

        fieldWrap.classList.remove('pfb-field-error');

        const error = fieldWrap.querySelector('.pfb-error');
        if (error) error.remove();
    }
});



