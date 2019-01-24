import Server from './Server';
import Language from './Language';

class HeaderUser
{
    constructor()
    {
        this.uiButton = $('.btn-settings');
        this.uiModal = $('.modal-settings');

        console.log(localStorage.getItem('server'));

        // set current server
        this.uiModal.find('select.servers').val(
            localStorage.getItem('server')
        );

        // set current language
        this.uiModal.find('select.languages').val(
            localStorage.getItem('language')
        );
    }

    watch()
    {
        this.uiButton.on('click', event => {
            this.uiModal.toggleClass('open');
        });

        $(document).mouseup(event => {
            const buttons = this.uiButton;
            const nav = this.uiModal;

            // if the target of the click isn't the container nor a descendant of the container
            if (!buttons.is(event.target) && buttons.has(event.target).length === 0
                && !nav.is(event.target) && nav.has(event.target).length === 0) {

                this.uiModal.removeClass('open');
            }
        });

        // server select
        this.uiModal.find('.servers').on('change', event => {
            Server.setServer($(event.currentTarget).val());
        });

        // language select
        this.uiModal.find('.languages').on('change', event => {
            Language.setLanguage($(event.currentTarget).val());
        });
    }
}

export default new HeaderUser;
