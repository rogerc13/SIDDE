<style>
    * {
        box-sizing: content-box;
    }
    footer {
        width: 100%;
        display: flex;
        flex-direction: row;
    }

    footer>div {
        width: 100%;
        height: 20px;
        font-size: 10rem;
        margin-right: 30px;
        display: flex;
        justify-content: flex-end;

    }
</style>
<footer>
    <div>
        <span>Pág </span>
        <span class="pageNumber"></span>
        <span> / </span>
        <span class="totalPages"></span>
    </div>
</footer>