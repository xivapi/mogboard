class Server
{
    constructor()
    {
        this.default = 'Phoenix';
    }

    init()
    {
        let server = localStorage.getItem('server');

        // default server if non exist
        localStorage.setItem('server', server ? server : this.default);
    }

    getServer()
    {
        return localStorage.getItem('server');
    }

    setServer(server)
    {
        localStorage.setItem('server', server);
        this.init();
    }
}

export default new Server;
