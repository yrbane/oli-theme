/**
 * Widget de réservation Olikalari.
 *
 * - Hydrate les blocs `.oli-booking` à partir du JSON inline `[data-oli-config]`.
 * - Sélecteur de service.
 * - Navigation hebdomadaire (← / →).
 * - Liste des créneaux libres, fetch sur `oli/v1/calendar/slots`.
 * - Modale de réservation, POST sur `oli/v1/calendar/bookings`.
 *
 * Pure ES module, sans dépendance externe (pas de jQuery, pas de framework).
 */
const fmtDate = (iso) => {
  const d = new Date(iso);
  return d.toLocaleString(undefined, {
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
};

class BookingWidget {
  constructor(root) {
    this.root = root;
    const cfg = JSON.parse(root.querySelector('[data-oli-config]').textContent);
    this.config = cfg;
    this.services = cfg.services || [];
    this.currentService = cfg.preselect && this.services.find((s) => s.id === cfg.preselect)
      ? cfg.preselect
      : (this.services[0]?.id || '');
    this.weekRef = new Date();
    this.servicePicker = root.querySelector('[data-oli-service-picker]');
    this.weekNav       = root.querySelector('[data-oli-week-nav]');
    this.slotsHost     = root.querySelector('[data-oli-slots]');
    this.modal         = root.querySelector('[data-oli-modal]');
    this.form          = root.querySelector('[data-oli-form]');
    this.formError     = root.querySelector('[data-oli-form-error]');
    this.formSuccess   = root.querySelector('[data-oli-form-success]');
    this.cancelBtn     = root.querySelector('[data-oli-modal-cancel]');
    this.submitBtn     = root.querySelector('[data-oli-submit]');
    this.modalTitle    = root.querySelector('[data-oli-modal-title]');

    this.renderServicePicker();
    this.renderWeekNav();
    this.bindForm();
    this.loadSlots();
  }

  renderServicePicker() {
    if (!this.services.length) {
      this.servicePicker.innerHTML = `<p>${this.config.i18n.noSlots}</p>`;
      return;
    }
    const label = document.createElement('label');
    label.className = 'oli-booking__service-label';
    label.textContent = this.config.i18n.selectService + ' : ';
    const select = document.createElement('select');
    select.className = 'oli-booking__service-select';
    this.services.forEach((s) => {
      const opt = document.createElement('option');
      opt.value = s.id;
      opt.textContent = `${s.labelFr} (${s.durationMinutes} min)`;
      if (s.id === this.currentService) opt.selected = true;
      select.appendChild(opt);
    });
    select.addEventListener('change', () => {
      this.currentService = select.value;
      this.loadSlots();
    });
    label.appendChild(select);
    this.servicePicker.replaceChildren(label);
  }

  renderWeekNav() {
    this.weekNav.innerHTML = '';
    const prev = document.createElement('button');
    prev.type = 'button';
    prev.className = 'oli-booking__nav-btn';
    prev.textContent = '← ' + this.config.i18n.prevWeek;
    prev.addEventListener('click', () => {
      this.weekRef.setDate(this.weekRef.getDate() - 7);
      this.loadSlots();
    });
    const next = document.createElement('button');
    next.type = 'button';
    next.className = 'oli-booking__nav-btn';
    next.textContent = this.config.i18n.nextWeek + ' →';
    next.addEventListener('click', () => {
      this.weekRef.setDate(this.weekRef.getDate() + 7);
      this.loadSlots();
    });
    const label = document.createElement('span');
    label.className = 'oli-booking__nav-label';
    label.textContent = this.weekRef.toLocaleDateString();
    this.weekNav.append(prev, label, next);
    this.weekLabel = label;
  }

  async loadSlots() {
    if (!this.currentService) {
      this.slotsHost.innerHTML = '';
      return;
    }
    this.slotsHost.setAttribute('aria-busy', 'true');
    this.slotsHost.innerHTML = '<p>…</p>';
    const isoDate = this.weekRef.toISOString().slice(0, 10);
    try {
      const url = `${this.config.restBase}/slots?service=${encodeURIComponent(this.currentService)}&from=${isoDate}`;
      const response = await fetch(url, { headers: { Accept: 'application/json' } });
      const json = await response.json();
      if (this.weekLabel && json.weekFrom) {
        this.weekLabel.textContent = json.weekFrom + ' → ' + json.weekTo;
      }
      this.renderSlots(json.items || []);
    } catch (e) {
      this.slotsHost.innerHTML = `<p>${this.config.i18n.error}</p>`;
    } finally {
      this.slotsHost.removeAttribute('aria-busy');
    }
  }

  renderSlots(items) {
    if (!items.length) {
      this.slotsHost.innerHTML = `<p class="oli-booking__empty">${this.config.i18n.noSlots}</p>`;
      return;
    }
    const list = document.createElement('ul');
    list.className = 'oli-booking__slot-list';
    items.forEach((slot) => {
      const li = document.createElement('li');
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'oli-booking__slot-btn';
      btn.textContent = fmtDate(slot.start);
      btn.addEventListener('click', () => this.openModal(slot));
      li.appendChild(btn);
      list.appendChild(li);
    });
    this.slotsHost.replaceChildren(list);
  }

  bindForm() {
    this.cancelBtn.addEventListener('click', () => this.closeModal());
    this.form.addEventListener('submit', async (e) => {
      e.preventDefault();
      this.submitBtn.disabled = true;
      const orig = this.submitBtn.textContent;
      this.submitBtn.textContent = this.config.i18n.submitting;
      this.formError.hidden = true;
      this.formSuccess.hidden = true;
      try {
        const data = Object.fromEntries(new FormData(this.form).entries());
        data.rendered_at = parseInt(data.rendered_at || '0', 10);
        data.lang = document.documentElement.lang?.split('-')[0] || 'fr';
        const res = await fetch(`${this.config.restBase}/bookings`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data),
        });
        const json = await res.json();
        if (json.success) {
          this.formSuccess.hidden = false;
          this.formSuccess.textContent = this.config.i18n.success;
          this.form.reset();
          setTimeout(() => { this.closeModal(); this.loadSlots(); }, 1800);
        } else {
          this.formError.hidden = false;
          this.formError.textContent = json.message || this.config.i18n.error;
        }
      } catch (e) {
        this.formError.hidden = false;
        this.formError.textContent = this.config.i18n.error;
      } finally {
        this.submitBtn.disabled = false;
        this.submitBtn.textContent = orig;
      }
    });
  }

  openModal(slot) {
    const f = this.form;
    f.querySelector('[data-oli-form-service]').value = slot.service;
    f.querySelector('[data-oli-form-start]').value = slot.start;
    this.modalTitle.textContent = fmtDate(slot.start);
    this.formError.hidden = true;
    this.formSuccess.hidden = true;
    if (typeof this.modal.showModal === 'function') {
      this.modal.showModal();
    } else {
      this.modal.setAttribute('open', '');
    }
  }

  closeModal() {
    if (typeof this.modal.close === 'function') {
      this.modal.close();
    } else {
      this.modal.removeAttribute('open');
    }
  }
}

const init = () => {
  document.querySelectorAll('.oli-booking').forEach((root) => {
    if (!root.dataset.oliInit) {
      new BookingWidget(root);
      root.dataset.oliInit = '1';
    }
  });
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
