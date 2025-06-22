const MGOM =  {
    quillInstance: null,
    notyf : null,
    notyfOptions: {
        duration: 4000,
        dismissible: true,
        ripple: false,
        position: {
            x: 'right',
            y: 'top',
        },
        types: [
            {
                type: 'success',
                className: 'notyf__toast--success',
                icon: {
                    className: 'material-icons',
                    tagName: 'i',
                    text: 'check_circle',
                },
            },
            {
                type: 'error',
                className: 'notyf__toast--error',
                icon: {
                    className: 'material-icons',
                    tagName: 'i',
                    text: 'error_outline',
                },
            },
            {
                type: 'info',
                className: 'notyf__toast--info',
                icon: {
                    className: 'material-icons',
                    tagName: 'i',
                    text: 'info',
                },
            },
            {
                type: 'warning',
                className: 'notyf__toast--warning',
                icon: {
                    className: 'material-icons',
                    tagName: 'i',
                    text: 'warning',
                },
            },
        ],
    },

    init: function() {
        MGOM.initNotify();
        MGOM.initModalCompte();
        MGOM.initSelect2();
        MGOM.initDeleteNotification();
        MGOM.initModalUser();
        MGOM.initDropdownNotif();

        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = {
                damping: '0.5'
            }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }
    },
    initNotify: function() {
        if (MGOM.notyf === null) {
            MGOM.notyf = new Notyf(MGOM.notyfOptions);
        }
    },
    initImageModalCompte: function() {
        const options = document.querySelectorAll('#image-selector .image-option');
        const select = document.querySelector('select[name$="[image]"]');

        options.forEach(el => {
            el.addEventListener('click', () => {
                options.forEach(opt => opt.classList.remove('selected'));
                el.classList.add('selected');
                select.value = el.dataset.image;
            });
        });

        // Synchronise à l'ouverture si valeur déjà sélectionnée (utile si formulaire réaffiché)
        const currentValue = select.value;
        options.forEach(opt => {
            if (opt.dataset.image === currentValue) {
                opt.classList.add('selected');
            }
        });
    },
    initModalCompte: function() {
      const modal = document.getElementById('accountModal');
      const modalBody = document.getElementById('accountModalBody');
      const modalTitle = document.getElementById('accountModalTitle');

      if (!modal || !modalBody) return;

      modal.addEventListener('show.bs.modal', event => {
        const trigger = event.relatedTarget;
        const url = trigger.getAttribute('data-url');
        const title = trigger.getAttribute('data-title');
        modalTitle.innerHTML = title;

        if (!url) return;

        // Affiche un loader temporaire
        modalBody.innerHTML = '<div class="text-center text-muted">Chargement...</div>';

        // Charge le formulaire
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(response => {
            if (!response.ok) throw new Error('Erreur lors du chargement du formulaire');
            return response.text();
          })
          .then(html => {
            modalBody.innerHTML = html;
            document.querySelectorAll('#image-selector .image-option').forEach(el => {
                el.addEventListener('click', () => {
                    document.querySelectorAll('#image-selector .image-option').forEach(opt => opt.classList.remove('selected'));
                    el.classList.add('selected');
                    document.querySelector('select[name$="[image]"]').value = el.dataset.image;
                });
            });
          })
          .catch(err => {
            console.error(err);
            modalBody.innerHTML = '<div class="text-danger">Erreur de chargement.</div>';
          });
      });
    },
    initModalUser: function() {
      const modal = document.getElementById('userModal');
      const modalBody = document.getElementById('userModalBody');

      if (!modal || !modalBody) return;

      modal.addEventListener('show.bs.modal', event => {
        const trigger = event.relatedTarget;
        const url = trigger.getAttribute('data-url');
        const title = trigger.getAttribute('data-title');

        if (!url) return;

        // Affiche un loader temporaire
        modalBody.innerHTML = '<div class="text-center text-muted">Chargement...</div>';

        // Charge le formulaire
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(response => {
            if (!response.ok) throw new Error('Erreur lors du chargement du formulaire');
            return response.text();
          })
          .then(html => {
            modalBody.innerHTML = html;
          })
          .catch(err => {
            console.error(err);
            modalBody.innerHTML = '<div class="text-danger">Erreur de chargement.</div>';
          });
      });
    },
    initSelect2: function () {
        $('.select2').each(function () {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2().on('select2:open', function () {
                    const searchField = document.querySelector('.select2-container--open .select2-search__field');
                    if (searchField) {
                        searchField.focus();
                    }
                });
            }
        });
    },
    initDeleteNotification: function() {
        const buttons = document.querySelectorAll('.delete-notification-btn');
        const btnDelAll = document.getElementById('deleteAllNotification');

        btnDelAll.addEventListener('click', function() {
            window.location = '/notification/delete';
        });


	    buttons.forEach(btn => {
		    btn.addEventListener('click', function () {
			    const notifId = this.dataset.id;

			    fetch(`/notifications/${notifId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => {
                    if (response.ok) {
                        // Supprimer visuellement
                        const el = document.getElementById(`notification-${notifId}`);
                        el.classList.add('fade');
                        setTimeout(() => el.remove(), 300);

                        // Décrémente le compteur
                        const countEl = document.getElementById('countNotif');
                        if (countEl) {
                            let count = parseInt(countEl.textContent);
                            if (!isNaN(count) && count > 0) {
                                countEl.textContent = count - 1;
                            }
                        }

                        // Ajoute la classe d'animation
                        const notifContainer = document.getElementById('notifContainer');
                        notifContainer.classList.add('pulse-glow');

                        notifContainer.addEventListener('animationend', () => {
                            notifContainer.classList.remove('pulse-glow');
                            
                            // Enchaîner avec un petit shake
                            notifContainer.classList.add('shake');
                            notifContainer.addEventListener('animationend', () => {
                                notifContainer.classList.remove('shake');
                            }, { once: true });
                        }, { once: true });
                    }
                });
            });
        });
    },
    initDropdownNotif: function() {
        if (document.querySelector('.notification-scroll')) {
            const container = document.querySelector('.notification-scroll');
            const ps = new PerfectScrollbar(container, {
                wheelPropagation: false
            });
        }

        const dropdown = document.querySelector('#dropdownMenuButton'); // ou ton bouton cloche

        dropdown.addEventListener('shown.bs.dropdown', function () {
            const container = document.querySelector('.notification-scroll');
            if (container) {
                if (!container._psInstance) {
                    container._psInstance = new PerfectScrollbar(container, {
                        wheelPropagation: false
                    });
                } else {
                    container._psInstance.update();
                }
            }
        });

    },

    activerEditionQuill: function(element) {
        if (MGOM.quillInstance) return; // empêcher plusieurs éditions en même temps

        const contenuInitial = element.innerHTML;
        const historiqueId = element.dataset.historiqueId;

        // Crée un conteneur temporaire
        const editor = document.createElement('div');
        editor.innerHTML = contenuInitial;
        element.replaceWith(editor);

        const Size = Quill.import('attributors/style/size');
        Quill.register(Size, true);

        // Initialiser Quill en inline
        MGOM.quillInstance = new Quill(editor, {
            theme: 'bubble' // ou 'snow' si tu veux la barre d’outil, mais 'bubble' est discret
        });

        // À chaque changement, applique la classe aux <p>
        MGOM.quillInstance.on('text-change', () => {
            const paras = MGOM.quillInstance.root.querySelectorAll('p');
            paras.forEach(p => p.classList.add('text-xs'));
        });

        // Événement de sauvegarde automatique quand focus perdu
        MGOM.quillInstance.on('selection-change', function(range) {
            if (range === null) {
                const contenuModifie = editor.querySelector('.ql-editor').innerHTML.trim();

                // Revenir à l’affichage normal
                const affichage = document.createElement('div');
                affichage.className = "blockquote text-light ps-3 border-start border-4 border-primary fst-italic text-xs";
                affichage.setAttribute("ondblclick", "MGOM.activerEditionQuill(this)");
                affichage.setAttribute("data-historique-id", historiqueId);
                affichage.innerHTML = contenuModifie;

                editor.replaceWith(affichage);
                MGOM.quillInstance = null;

                MGOM.saveCommentaire(historiqueId, contenuModifie);
            }
        });
    },

    saveCommentaire: function(id, contenu) {
        fetch(`/historique/${id}/commentaire`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ contenu })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Commentaire sauvegardé !', data);
        })
        .catch(error => {
            console.error('Erreur lors de la sauvegarde :', error);
        });
    },

    notify: function(message, type = 'success', duration = 4000) {
        if (MGOM.notyf === null) {
            MGOM.init();
        }
        MGOM.notyf.open({
            type: type,
            message: message,
            duration: duration,
        });
    },

    animeCount: function(id, countTo) {
        const countUp = new CountUp(id, countTo);
        if (!countUp.error) {
            countUp.start();
        } else {
            console.error(countUp.error);
        }
    },

    fetchData: function(url, method = 'GET') {
        return new Promise((resolve, reject) => {
            var xhr = new XMLHttpRequest();

            xhr.open(method, url, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        resolve(data);
                    } catch (e) {
                        reject('Erreur lors de l\'analyse JSON: ' + e);
                    }
                } else {
                    reject('Erreur lors de la requête: ' + xhr.statusText);
                }
            };

            xhr.onerror = function() {
                reject('Erreur réseau');
            };

            xhr.send();
        });
    }
}

function onLoadApp() {
    MGOM.init();
}

window.addEventListener('load', onLoadApp);