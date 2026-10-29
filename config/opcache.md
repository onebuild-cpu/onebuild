Since you are building this from scratch, you have total control. Just ensure these 3 things:
Enable OPcache in Production: Add this to your live server's php.ini:

    opcache.enable=1
    opcache.memory_consumption=256
    opcache.interned_strings_buffer=16
    opcache.max_accelerated_files=10000
    opcache.revalidate_freq=0  ; Set to 0 in production
    opcache.validate_timestamps=0  ; Set to 0 in production
    opcache.fast_shutdown=1
    opcache.enable_cli=1