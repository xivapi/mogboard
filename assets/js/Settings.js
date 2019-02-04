import Server from './Server';
import Language from './Language';
import Modals from './Modals';

class Settings
{
    constructor()
    {
        this.uiModal        = $('.modal_settings');
        this.uiModalButton  = $('.btn-settings');

        // always ensure server is stored in cookie
        if (localStorage.getItem('server')) {
            Server.setServer(localStorage.getItem('server'));
        }

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
        Modals.add(this.uiModal, this.uiModalButton);

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

export default new Settings;
