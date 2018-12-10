import Server from './Server';
import Language from './Language';

class HeaderUser
{
    constructor()
    {
        this.uiButton = $('.settings-btn');
        this.uiMenu = $('.settings-menu');

        console.log(localStorage.getItem('server'));

        // set current server
        this.uiMenu.find('select.servers').val(
            localStorage.getItem('server')
        );

        // set current language
        this.uiMenu.find('select.languages').val(
            localStorage.getItem('language')
        );
    }

    watch()
    {
        this.uiButton.on('click', event => {
            this.uiMenu.toggleClass('open');
        });

        $(document).mouseup(event => {
            const buttons = this.uiButton;
            const nav = this.uiMenu;

            // if the target of the click isn't the container nor a descendant of the container
            if (!buttons.is(event.target) && buttons.has(event.target).length === 0
                && !nav.is(event.target) && nav.has(event.target).length === 0) {

                this.uiMenu.removeClass('open');
            }
        });

        // server select
        this.uiMenu.find('.servers').on('change', event => {
            console.log('change');
            Server.setServer($(event.currentTarget).val());
        });

        // language select
        this.uiMenu.find('.languages').on('change', event => {
            Language.setLanguage($(event.currentTarget).val());
        });
    }
}

export default new HeaderUser;
