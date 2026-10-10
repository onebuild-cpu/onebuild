    </main>
    
    <style nonce="<?= $nonce ?>">
    .page-bottom-unhide{
        height:70px;
    }
    .site-footer {
        position: relative;
        left: 0;
        bottom: 0;
        width: 100%;
        height: auto;
        background-color: rgba(255,255,255,1);
        color: black;
        padding-top: 5px;
        padding-bottom: 5px;
    }
    </style>

    <div class="page-bottom-unhide">&nbsp;</div>    

    <footer class="site-footer">
        <hr>
        <div class="container">
            <p>&copy; <?= date('Y') ?> - Ready to build an amazing website !!!</p>
        </div>
    </footer>
    <script src="<?= $base ?>/assets/js/jquery.slim.min.js" nonce="<?= $nonce ?>"></script>
    <script src="<?= $base ?>/assets/js/bootstrap.bundle.min.js" nonce="<?= $nonce ?>"></script>
    <script src="<?= $base ?>/assets/js/summernote-bs5.min.js" nonce="<?= $nonce ?>"></script>
</body>
</html>
