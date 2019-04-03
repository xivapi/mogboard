import Popup from "./Popup";

const Cookie = require('js-cookie');
import Modals from './Modals';

class Settings
{
    constructor()
    {
        this.uiModal            = $('.modal_settings');
        this.uiModalButton      = $('.btn-settings');

        this.defaultLanguage    = 'en';
        this.storageKeyServer   = 'mogboard_server';
        this.storageKeyLanguage = 'mogboard_language';

        this.server             = this.getServer();
        this.language           = this.getLanguage();
    }

    init()
    {
        let server   = this.getServer(),
            language = this.getLanguage();

        // set default language, this isn't as precious as server
        this.setLanguage(this.defaultLanguage);

        // if not set, ask to set
        if (server === null || server.length === 0) {
            setTimeout(() => {
                this.uiModalButton.trigger('click');
            }, 500);

            return;
        }

        this.setServer(server);
        this.setLanguage(language);

        // set selected items
        this.uiModal.find('select.servers').val(server);
        this.uiModal.find('select.languages').val(language);
    }

    watch()
    {
        Modals.add(this.uiModal, this.uiModalButton);

        // server select
        this.uiModal.find('.servers').on('change', event => {
            this.setServer($(event.currentTarget).val());
            Popup.success('Cookie Set', 'Page reloading, please wait!');
            location.reload(true);
        });

        // language select
        this.uiModal.find('.languages').on('change', event => {
            this.setLanguage($(event.currentTarget).val());
        });
    }

    setServer(server)
    {
        localStorage.setItem(this.storageKeyServer, server);
        Cookie.set(this.storageKeyServer, server, { expires: 365, path: '/' });
    }

    getServer()
    {
        return localStorage.getItem(this.storageKeyServer);
    }

    setLanguage(language)
    {
        localStorage.setItem(this.storageKeyLanguage, language);
        Cookie.set(this.storageKeyLanguage, language, { expires: 365, path: '/' });
    }

    getLanguage()
    {
        return localStorage.getItem(this.storageKeyLanguage);
    }
}

export default new Settings;
