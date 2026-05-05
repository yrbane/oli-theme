/**
 * SEO metabox UX live (admin) — compteurs longueur, preview SERP, gauge score
 * approximatif. Le score définitif est calculé côté PHP au save.
 */
export function initSeoMetabox() {
    const root = document.querySelector('.oli-seo-metabox');
    if (!root) {
        return;
    }

    const titleInput = root.querySelector('#oli-seo-title');
    const descInput = root.querySelector('#oli-seo-description');
    const keywordInput = root.querySelector('#oli-seo-keyword');

    const titleCounter = ensureCounter(titleInput, 30, 65);
    const descCounter = ensureCounter(descInput, 120, 158);

    const previewTitle = ensurePreviewElement(root, 'oli-seo-preview__title', 'p');
    const previewUrl = ensurePreviewElement(root, 'oli-seo-preview__url', 'p');
    const previewDesc = ensurePreviewElement(root, 'oli-seo-preview__desc', 'p');

    const update = () => {
        if (titleInput && titleCounter) {
            updateCounter(titleCounter, titleInput.value.length, 30, 65);
        }
        if (descInput && descCounter) {
            updateCounter(descCounter, descInput.value.length, 120, 158);
        }
        if (previewTitle) previewTitle.textContent = titleInput?.value || 'Titre de la page';
        if (previewDesc) previewDesc.textContent = descInput?.value || 'Méta description…';
        if (previewUrl) previewUrl.textContent = window.location.origin || 'https://exemple.com';
    };

    [titleInput, descInput, keywordInput].forEach((input) => {
        if (input) input.addEventListener('input', update);
    });

    update();
}

function ensureCounter(input, min, max) {
    if (!input || !input.parentElement) return null;
    let counter = input.parentElement.querySelector('.oli-seo-counter');
    if (counter) return counter;
    counter = document.createElement('span');
    counter.className = 'oli-seo-counter';
    input.parentElement.appendChild(counter);
    return counter;
}

function updateCounter(el, length, min, max) {
    el.textContent = length + ' / ' + max + ' caractères';
    el.classList.remove('oli-seo-counter--good', 'oli-seo-counter--warn', 'oli-seo-counter--bad');
    if (length >= min && length <= max) {
        el.classList.add('oli-seo-counter--good');
    } else if (length > 0 && (length >= min - 10 || length <= max + 10)) {
        el.classList.add('oli-seo-counter--warn');
    } else {
        el.classList.add('oli-seo-counter--bad');
    }
}

function ensurePreviewElement(root, className, tag) {
    let preview = root.querySelector('.oli-seo-preview');
    if (!preview) {
        preview = document.createElement('div');
        preview.className = 'oli-seo-preview';
        root.appendChild(preview);
    }
    let el = preview.querySelector('.' + className);
    if (!el) {
        el = document.createElement(tag);
        el.className = className;
        preview.appendChild(el);
    }
    return el;
}
