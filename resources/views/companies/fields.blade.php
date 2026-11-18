<x-input name="name" label="Nama perusahaan" :value="$data?->name" required maxlength="150" />
<x-input name="npwp" label="NPWP pemotong (16 digit)" :value="$data?->npwp" required pattern="[0-9]{16}" maxlength="16" inputmode="numeric" />
<x-input name="city" label="Kota" :value="$data?->city" required maxlength="100" />
<x-input name="address" label="Alamat" :value="$data?->address" maxlength="255" />
<x-input name="signer_name" label="Nama penandatangan" :value="$data?->signer_name" required maxlength="150" />
<x-input name="signer_npwp" label="NPWP/NIK penandatangan (16 digit)" :value="$data?->signer_npwp" required pattern="[0-9]{16}" maxlength="16" inputmode="numeric" />
