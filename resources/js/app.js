// declara·gt — JS vanilla, sin frameworks de frontend (§1 del brief).

function inicializarBannerPrivacidad() {
    const banner = document.getElementById('banner-privacidad');
    const cerrar = document.getElementById('cerrar-banner-privacidad');
    if (!banner || !cerrar) return;

    if (!localStorage.getItem('declaragt_banner_oculto')) {
        banner.style.display = 'flex';
    }

    cerrar.addEventListener('click', () => {
        banner.style.display = 'none';
        localStorage.setItem('declaragt_banner_oculto', '1');
    });
}

function inicializarEdicionInlineDocumentos() {
    const selects = document.querySelectorAll('[data-tipo-documento]');
    if (!selects.length) return;

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    selects.forEach((select) => {
        const valorOriginal = select.value;

        select.addEventListener('change', async () => {
            const url = select.dataset.tipoDocumento;
            const indicador = select.closest('[data-fila-documento]')?.querySelector('[data-guardado]');

            try {
                const respuesta = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token || '',
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ tipo: select.value }),
                });

                if (!respuesta.ok) throw new Error('No se pudo guardar');

                if (indicador) {
                    indicador.textContent = 'Guardado';
                    indicador.style.opacity = '1';
                    setTimeout(() => { indicador.style.opacity = '0'; }, 1500);
                }
            } catch (error) {
                select.value = valorOriginal;
                alert('No se pudo actualizar el tipo. Intenta de nuevo.');
            }
        });
    });
}

function inicializarToggleCreditoDocumentos() {
    const casillas = document.querySelectorAll('[data-credito-documento]');
    if (!casillas.length) return;

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    casillas.forEach((casilla) => {
        casilla.addEventListener('change', async () => {
            const url = casilla.dataset.creditoDocumento;
            const valorOriginal = !casilla.checked;
            const indicador = casilla.closest('td')?.querySelector('[data-guardado-credito]');

            try {
                const respuesta = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token || '',
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ genera_credito: casilla.checked }),
                });

                if (!respuesta.ok) throw new Error('No se pudo guardar');

                if (indicador) {
                    indicador.textContent = 'Guardado';
                    indicador.style.opacity = '1';
                    setTimeout(() => { indicador.style.opacity = '0'; }, 1500);
                }
            } catch (error) {
                casilla.checked = valorOriginal;
                alert('No se pudo actualizar el crédito fiscal. Intenta de nuevo.');
            }
        });
    });
}

function inicializarDropzone() {
    const zona = document.getElementById('dropzone');
    const input = document.getElementById('archivo-input');
    const form = document.getElementById('form-importar');
    if (!zona || !input || !form) return;

    ['dragenter', 'dragover'].forEach((evento) => {
        zona.addEventListener(evento, (e) => {
            e.preventDefault();
            zona.style.borderColor = 'var(--verde-quetzal)';
            zona.style.background = 'rgba(14, 107, 79, 0.03)';
        });
    });

    ['dragleave', 'drop'].forEach((evento) => {
        zona.addEventListener(evento, (e) => {
            e.preventDefault();
            zona.style.borderColor = '';
            zona.style.background = '';
        });
    });

    zona.addEventListener('drop', (e) => {
        if (!e.dataTransfer?.files?.length) return;
        input.files = e.dataTransfer.files;
        form.submit();
    });
}

function inicializarConfirmacionReinicio() {
    const formulario = document.querySelector('[data-form-reiniciar]');
    if (!formulario) return;

    const nitActual = formulario.dataset.nitActual;
    const campoConfirmar = formulario.querySelector('[data-confirmar-nit]');
    const boton = formulario.querySelector('[data-btn-reiniciar]');

    campoConfirmar.addEventListener('input', () => {
        boton.disabled = campoConfirmar.value.trim() !== nitActual;
    });

    formulario.addEventListener('submit', (evento) => {
        if (campoConfirmar.value.trim() !== nitActual) {
            evento.preventDefault();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    inicializarBannerPrivacidad();
    inicializarEdicionInlineDocumentos();
    inicializarToggleCreditoDocumentos();
    inicializarDropzone();
    inicializarConfirmacionReinicio();
});
