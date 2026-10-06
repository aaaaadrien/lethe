// Lethe - chunked upload engine (shared by the send-file view and the public deposit page).
'use strict';

window.Lethe = window.Lethe || {};

Lethe.upload = {
  /**
   * Splits a file into chunks and POSTs them sequentially to chunkEndpoint
   * (multipart form). Extra fields are appended to every request.
   *
   * @returns {Promise<{uploadId: string, totalChunks: number}>}
   */
  async sendChunks(file, chunkEndpoint, extraFields = {}, onProgress = null) {
    const uploadId = Lethe.upload.uuid();
    const totalChunks = Math.max(1, Math.ceil(file.size / Lethe.config.chunkSize));

    for (let index = 0; index < totalChunks; index++) {
      const start = index * Lethe.config.chunkSize;
      const end = Math.min(start + Lethe.config.chunkSize, file.size);
      const chunk = file.slice(start, end);

      const fd = new FormData();
      if (Lethe.state.csrfToken) {
        fd.append('csrf_token', Lethe.state.csrfToken);
      }
      fd.append('upload_id', uploadId);
      fd.append('index', index);
      for (const [key, value] of Object.entries(extraFields)) {
        fd.append(key, value);
      }
      fd.append('chunk', chunk, 'chunk');

      const res = await fetch(chunkEndpoint, {
        method: 'POST',
        body: fd,
        headers: Lethe.state.csrfToken ? { 'X-CSRF-Token': Lethe.state.csrfToken } : {},
      });

      let data = null;
      try {
        data = await res.json();
      } catch (e) {
        data = null;
      }
      if (!res.ok || !data || data.status !== 'ok') {
        throw new Error((data && data.message) ? data.message : Lethe.i18n.t('error.chunk_send_failed'));
      }

      if (onProgress) {
        onProgress(index + 1, totalChunks);
      }
    }

    return { uploadId, totalChunks };
  },

  uuid() {
    return 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'.replace(/x/g, () =>
      Math.floor(Math.random() * 16).toString(16));
  },
};
