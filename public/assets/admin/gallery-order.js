(() => {
    const grid = document.getElementById('gallery-order-grid');
    const form = document.getElementById('gallery-order-form');
    if (!grid || !form) return;
    let dragged = null;
    let dirty = false;
    const update = (changed = true) => {
        [...grid.querySelectorAll('.gallery-order-card')].forEach((card, index, cards) => {
            card.querySelector('[data-position]').textContent = index + 1;
            card.querySelector('[data-move="-1"]').disabled = index === 0;
            card.querySelector('[data-move="1"]').disabled = index === cards.length - 1;
        });
        if (changed) {
            dirty = true;
            document.getElementById('gallery-order-status').textContent = 'Есть несохранённые изменения';
        }
    };
    grid.addEventListener('dragstart', event => {
        dragged = event.target.closest('.gallery-order-card');
        if (!dragged) return;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', dragged.querySelector('input').value);
        dragged.classList.add('dragging');
    });
    grid.addEventListener('dragover', event => {
        if (!dragged) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
    });
    grid.addEventListener('drop', event => {
        if (!dragged) return;
        event.preventDefault();
        const target = event.target.closest('.gallery-order-card');
        if (target && target !== dragged) {
            const cards = [...grid.children];
            grid.insertBefore(dragged, cards.indexOf(dragged) < cards.indexOf(target) ? target.nextSibling : target);
            update();
        }
    });
    grid.addEventListener('dragend', () => {
        if (dragged) dragged.classList.remove('dragging');
        dragged = null;
    });
    grid.addEventListener('click', event => {
        const button = event.target.closest('[data-move]');
        if (!button) return;
        const card = button.closest('.gallery-order-card');
        const earlier = button.dataset.move === '-1';
        const neighbor = earlier ? card.previousElementSibling : card.nextElementSibling;
        if (neighbor) {
            grid.insertBefore(card, earlier ? neighbor : neighbor.nextSibling);
            update();
            if (button.disabled) card.querySelector(earlier ? '[data-move="1"]' : '[data-move="-1"]').focus();
            else button.focus();
        }
    });
    window.addEventListener('beforeunload', event => {
        if (dirty) { event.preventDefault(); event.returnValue = ''; }
    });
    form.addEventListener('submit', () => { dirty = false; });
    update(false);
})();
