(() => {
    const selector = '.infrastructure-sortable';
    let dragged = null;
    const rows = collection => [...collection.querySelectorAll('.field-collection-item')];
    const refresh = collection => {
        rows(collection).forEach((row, index, items) => {
            if (!row.querySelector('.infrastructure-order-controls')) {
                const controls = document.createElement('span');
                controls.className = 'infrastructure-order-controls';
                for (const [action, text, label] of [
                    ['drag', '↕', 'Перетащить пункт'],
                    ['up', '↑', 'Переместить выше'],
                    ['down', '↓', 'Переместить ниже'],
                ]) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'btn btn-light';
                    button.dataset.orderAction = action;
                    button.textContent = text;
                    button.title = label;
                    button.setAttribute('aria-label', label);
                    if (action === 'drag') button.draggable = true;
                    controls.append(button);
                }
                row.prepend(controls);
            }
            row.querySelector('[data-order-action="up"]').disabled = index === 0;
            row.querySelector('[data-order-action="down"]').disabled = index === items.length - 1;
            row.classList.toggle('field-collection-item-first', index === 0);
            row.classList.toggle('field-collection-item-last', index === items.length - 1);
        });
    };
    const changed = collection => {
        refresh(collection);
        const input = collection.querySelector('input');
        if (input) input.dispatchEvent(new Event('change', { bubbles: true }));
    };
    const init = () => document.querySelectorAll(selector).forEach(refresh);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
    document.addEventListener('ea.collection.item-added', init);
    document.addEventListener('ea.collection.item-removed', init);
    document.addEventListener('click', event => {
        const button = event.target.closest(`${selector} [data-order-action]`);
        if (!button || button.dataset.orderAction === 'drag') return;
        const row = button.closest('.field-collection-item');
        const collection = row.closest(selector);
        const items = rows(collection);
        const index = items.indexOf(row);
        const up = button.dataset.orderAction === 'up';
        const target = items[index + (up ? -1 : 1)];
        if (!target) return;
        target.parentNode.insertBefore(row, up ? target : target.nextSibling);
        changed(collection);
        (button.disabled ? row.querySelector('[data-order-action="drag"]') : button).focus();
    });
    document.addEventListener('dragstart', event => {
        const handle = event.target.closest(`${selector} [data-order-action="drag"]`);
        if (!handle) return;
        dragged = handle.closest('.field-collection-item');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', 'infrastructure-order');
        event.dataTransfer.setDragImage(dragged, 20, 20);
        dragged.classList.add('infrastructure-dragging');
    });
    document.addEventListener('dragover', event => {
        if (!dragged || event.target.closest(selector) !== dragged.closest(selector)) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
    });
    document.addEventListener('drop', event => {
        if (!dragged || event.target.closest(selector) !== dragged.closest(selector)) return;
        event.preventDefault();
        const target = event.target.closest('.field-collection-item');
        if (!target || target === dragged) return;
        const collection = dragged.closest(selector);
        const items = rows(collection);
        target.parentNode.insertBefore(dragged, items.indexOf(dragged) < items.indexOf(target) ? target.nextSibling : target);
        changed(collection);
    });
    document.addEventListener('dragend', () => {
        if (dragged) dragged.classList.remove('infrastructure-dragging');
        dragged = null;
    });
    // Symfony maps collection children by numeric key, not by their DOM order.
    document.addEventListener('submit', event => {
        event.target.querySelectorAll(selector).forEach(collection => {
            rows(collection).forEach((row, index) => {
                const input = row.querySelector('input[name]');
                if (input) input.name = input.name.replace(/\[\d+\]$/, `[${index}]`);
            });
        });
    }, true);
})();
