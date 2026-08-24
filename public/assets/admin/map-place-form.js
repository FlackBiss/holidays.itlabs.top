document.addEventListener('DOMContentLoaded', () => {
    const typeField = document.querySelector('[name$="[type]"]');
    if (!typeField) return;

    const fieldContainer = (element) => element.closest('.form-group, .field-collection') || element;
    const clearFields = (containers) => containers.forEach((container) => {
        container.querySelectorAll('input, select, textarea').forEach((field) => {
            if (field.type === 'checkbox' || field.type === 'radio') field.checked = false;
            else field.value = '';
            field.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
    const setTabVisible = (className, visible) => {
        const pane = document.querySelector(`.tab-pane.${className}`);
        if (!pane) return;
        pane.hidden = !visible;
        const link = document.querySelector(`[href="#${pane.id}"]`);
        const item = link?.closest('.nav-item');
        if (item) item.hidden = !visible;
        if (!visible && pane.classList.contains('active')) {
            [...document.querySelectorAll('.form-tabs-tablist .nav-item')]
                .find((candidate) => !candidate.hidden)
                ?.querySelector('.nav-link')?.click();
        }
    };
    const updateVisibility = (clearHidden) => {
        const residential = typeField.value === 'residential';
        const residentialFields = [...document.querySelectorAll('.place-field-residential')];
        const infrastructureFields = [...document.querySelectorAll('.place-field-infrastructure')];
        residentialFields.forEach((field) => { fieldContainer(field).hidden = !residential; });
        infrastructureFields.forEach((field) => { fieldContainer(field).hidden = residential; });
        setTabVisible('place-tab-residential', residential);
        setTabVisible('place-tab-infrastructure', !residential);
        if (clearHidden) clearFields((residential ? infrastructureFields : residentialFields).map(fieldContainer));
    };
    typeField.addEventListener('change', () => updateVisibility(true));
    updateVisibility(false);
});
