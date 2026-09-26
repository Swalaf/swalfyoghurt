# Third-party notices

AI Code Studio includes the following open-source components. Their licences
allow commercial use; the notices below must be kept with the software.

| Component | Licence | Where |
|---|---|---|
| Laravel framework and its dependencies (Symfony, Carbon, Guzzle, Monolog, league/*, etc.) | MIT (mostly; see each package) | `vendor/` — each package ships its own `LICENSE` file |
| league/flysystem-aws-s3-v3, AWS SDK for PHP | MIT / Apache-2.0 | `vendor/` |
| Alpine.js 3.14.8 | MIT, © Caleb Porzio and contributors | `public/js/alpine.min.js` |
| QRCode.js 1.0.0 | MIT, © davidshimjs | `public/js/qrcode.min.js` |
| Geist and Geist Mono fonts | SIL Open Font License 1.1, © Vercel | `public/fonts/` (licence in `public/fonts/OFL.txt`) |

To list every PHP package and its licence:

```bash
composer licenses --no-dev
```

## MIT licence text

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
