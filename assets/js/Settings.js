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
        this.defaultTimezone    = 'UTC';
        this.defaultLeftNav     = 'on';

        this.storageKeyServer   = 'mogboard_server';
        this.storageKeyLanguage = 'mogboard_language';
        this.storageKeyTimezone = 'mogboard_timezone';
        this.storageKeyLeftNav  = 'mogboard_leftnav';

        this.server             = this.getServer();
        this.language           = this.getLanguage();
        this.timezone           = this.getTimezone();
        this.leftnav            = this.getLeftNav();
    }

    init()
    {
        let server   = this.getServer(),
            language = this.getLanguage(),
            timezone = this.getTimezone(),
            leftnav  = this.getLeftNav();

        language = language ? language : this.defaultLanguage;
        timezone = timezone ? timezone : this.defaultTimezone;
        leftnav  = leftnav ? leftnav : this.defaultLeftNav;

        console.log(leftnav);

        // if not set, ask to set
        if (server === null || server.length === 0) {
            setTimeout(() => {
                this.uiModalButton.trigger('click');
            }, 500);

            return;
        }

        this.setServer(server);
        this.setLanguage(language);
        this.setTimezone(timezone);
        this.setLeftNav(leftnav);

        // set selected items
        this.uiModal.find('select.servers').val(server);
        this.uiModal.find('select.languages').val(language);
        this.uiModal.find('select.timezones').val(timezone);
        this.uiModal.find('select.leftnav').val(leftnav);
    }

    watch()
    {
        Modals.add(this.uiModal, this.uiModalButton);

        // server select
        this.uiModal.find('.servers').on('change', event => {
            this.setServer($(event.currentTarget).val());
        });

        // language select
        this.uiModal.find('.languages').on('change', event => {
            this.setLanguage($(event.currentTarget).val());
        });

        // timezone select
        this.uiModal.find('.timezones').on('change', event => {
            this.setTimezone($(event.currentTarget).val());
        });

        // timezone select
        this.uiModal.find('.leftnav').on('change', event => {
            this.setLeftNav($(event.currentTarget).val());
        });

        this.uiModal.find('.btn-green').on('click', event => {
            Popup.success('Settings Saved', 'Refreshing site, please wait...');
            location.reload(true);
        })
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

    setTimezone(timezone)
    {
        localStorage.setItem(this.storageKeyTimezone, timezone);
        Cookie.set(this.storageKeyTimezone, timezone, { expires: 365, path: '/' });
    }

    getTimezone()
    {
        return localStorage.getItem(this.storageKeyTimezone);
    }

    setLeftNav(leftnav)
    {
        console.log(leftnav);
        localStorage.setItem(this.storageKeyLeftNav, leftnav);
        Cookie.set(this.storageKeyLeftNav, leftnav, { expires: 365, path: '/' });
    }

    getLeftNav()
    {
        return localStorage.getItem(this.storageKeyLeftNav);
    }
}

export default new Settings;
