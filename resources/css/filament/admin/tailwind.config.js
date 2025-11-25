import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/Clusters/Emitcfdi/**/*.php',
        './resources/views/filament/clusters/emitcfdi/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/awcodes/filament-table-repeater/resources/**/*.blade.php',
        './vendor/defstudio/filament-searchable-input/resources/**/*.blade.php'
    ],
}

