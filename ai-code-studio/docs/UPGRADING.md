# Upgrading

1. **Back up.** In Admin → License & updates, keep “Back up everything first” ticked (it runs as part of step 4), or back up your database and `.env` yourself.
2. **Download the new version.** Admin → License & updates → *Check for updates* → *Download* (needs an active licence with support). Or use the link in your purchase email.
3. **Upload the files** over your existing folder. **Keep** these:
   - `.env`
   - `storage/` (projects, published apps, logs, the install lock)
   - `public/uploads/` (your logo and favicon)

   On cPanel: upload the zip in File Manager, extract it next to your current folder, then copy everything except the three items above over the old folder.
4. **Finish.** Open Admin → License & updates and click **Finish update**. This backs up (if ticked), runs database migrations and clears caches.
5. If something goes wrong, restore the backup from `storage/app/backups/` and the previous files.

Notes:

- Upgrades never overwrite your settings, branding, users or projects.
- The version you are on is shown on the License & updates page. The version of the uploaded files is in `config/studio.php`.
- Command-line alternative: `php artisan migrate --force && php artisan optimize:clear`.
