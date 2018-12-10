class Language
{
    constructor()
    {
        this.default = 'eu';
    }

    init()
    {
        let language = localStorage.getItem('language');

        // default language if non exist
        localStorage.setItem('language', language ? language : this.default);
    }

    getLanguage()
    {
        return localStorage.getItem('language');
    }

    setLanguage(language)
    {
        localStorage.setItem('language', language);
        this.init();
    }
}

export default new Language;
