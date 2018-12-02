class Server
{
    init()
    {
        let server = localStorage.getItem('server');

        // default server if non exist
        server = server ? server : 'Phoenix';
        localStorage.setItem('server', server);
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
