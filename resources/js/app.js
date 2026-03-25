import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

function onlyDigits(value) {
	return (value || '').replace(/\D+/g, '');
}

function formatCpf(value) {
	const digits = onlyDigits(value).slice(0, 11);
	if (digits.length <= 3) return digits;
	if (digits.length <= 6) return `${digits.slice(0, 3)}.${digits.slice(3)}`;
	if (digits.length <= 9) return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6)}`;
	return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6, 9)}-${digits.slice(9, 11)}`;
}

function isValidCpf(cpf) {
	const digits = onlyDigits(cpf);
	if (digits.length !== 11) return false;
	if (/^(\d)\1{10}$/.test(digits)) return false;

	const calc = (length) => {
		let sum = 0;
		for (let i = 0; i < length; i += 1) {
			sum += Number(digits[i]) * ((length + 1) - i);
		}
		const mod = (sum * 10) % 11;
		return mod === 10 ? 0 : mod;
	};

	const d1 = calc(9);
	const d2 = calc(10);

	return d1 === Number(digits[9]) && d2 === Number(digits[10]);
}

function isCpfField(input) {
	if (!(input instanceof HTMLInputElement)) return false;
	if (input.type === 'hidden') return false;

	const name = (input.name || '').toLowerCase();
	const id = (input.id || '').toLowerCase();
	const dataMask = (input.dataset.mask || '').toLowerCase();

	return name.includes('cpf') || id.includes('cpf') || dataMask === 'cpf';
}

function bindCpfMask(input) {
	if (!isCpfField(input)) return;
	if (input.dataset.cpfMaskBound === '1') return;

	input.dataset.cpfMaskBound = '1';
	input.setAttribute('maxlength', '14');
	input.value = formatCpf(input.value);

	input.addEventListener('input', () => {
		input.value = formatCpf(input.value);
		input.setCustomValidity('');
	});

	input.addEventListener('blur', () => {
		const value = onlyDigits(input.value);
		if (value.length === 0) {
			input.setCustomValidity('');
			return;
		}

		if (!isValidCpf(value)) {
			input.setCustomValidity('CPF invalido. Verifique os digitos informados.');
			input.reportValidity();
			return;
		}

		input.setCustomValidity('');
	});
}

function initCpfInputs() {
	const cpfInputs = Array.from(document.querySelectorAll('input'));
	cpfInputs.forEach((input) => bindCpfMask(input));

	const observer = new MutationObserver((mutations) => {
		mutations.forEach((mutation) => {
			mutation.addedNodes.forEach((node) => {
				if (node instanceof HTMLInputElement) {
					bindCpfMask(node);
					return;
				}

				if (node instanceof HTMLElement) {
					node.querySelectorAll('input').forEach((input) => bindCpfMask(input));
				}
			});
		});
	});

	observer.observe(document.body, { childList: true, subtree: true });
}

function getToastContainer() {
	let container = document.getElementById('sa-toast-container');
	if (!container) {
		container = document.createElement('div');
		container.id = 'sa-toast-container';
		container.className = 'sa-toast-container';
		document.body.appendChild(container);
	}

	return container;
}

function showToast(message, type = 'info', timeout = 4200) {
	if (!message) return;

	const container = getToastContainer();
	const toast = document.createElement('div');
	toast.className = `sa-toast sa-toast-${type}`;
	toast.textContent = message;

	container.appendChild(toast);

	requestAnimationFrame(() => {
		toast.classList.add('is-visible');
	});

	window.setTimeout(() => {
		toast.classList.remove('is-visible');
		window.setTimeout(() => toast.remove(), 220);
	}, timeout);
}

window.saToast = showToast;

function initFormLoadingState() {
	document.querySelectorAll('form').forEach((form) => {
		form.addEventListener('submit', () => {
			const submitButtons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
			submitButtons.forEach((btn) => {
				if (btn.dataset.loadingApplied === '1') return;

				btn.dataset.loadingApplied = '1';
				btn.disabled = true;
				btn.classList.add('opacity-70', 'cursor-not-allowed');

				if (btn.tagName.toLowerCase() === 'button') {
					const original = btn.innerHTML;
					btn.dataset.originalHtml = original;
					const label = btn.dataset.loadingText || 'Processando...';
					btn.innerHTML = `<span class="sa-loading-dot"></span><span>${label}</span>`;
				}
			});
		});
	});
}

document.addEventListener('DOMContentLoaded', () => {
	initCpfInputs();
	initFormLoadingState();

	const body = document.body;
	const statusMessage = body?.dataset?.flashStatus;
	const errorMessage = body?.dataset?.flashError;

	if (statusMessage) {
		showToast(statusMessage, 'success');
	}

	if (errorMessage) {
		showToast(errorMessage, 'error');
	}
});
