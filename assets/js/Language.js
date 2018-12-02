class Language
{
    init()
    {
        let language = localStorage.getItem('language');

        // default language if non exist
        language = language ? language : 'eu';
        localStorage.setItem('language', language);
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
