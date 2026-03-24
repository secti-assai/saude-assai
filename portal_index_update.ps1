$file = 'resources/views/portal/index.blade.php'
$content = Get-Content $file -Raw
$cardRegex = '<!-- Robust Ficha Oficial -->([\s\S]*?)<div class="px-6 py-4 bg-gray-50 border-t\s*border-gray-100 w-full text-center">\s*<a href="#" class="text-sm font-bold\s*text-\[var\(--gov-primary\)\] hover:text-\[var\(--gov-primary-dark\)\]">Ver no Mapa\s*&rarr;</a>\s*</div>\s*</div>'
$newCard = '<!-- Robust Ficha Oficial -->
                  <div class="bg-white border rounded-lg border-gray-200 shadow-sm hover:shadow-lg transition flex flex-col">
                      <div class="p-1 min-h-[8px] bg-[var(--gov-primary)] w-full rounded-t-lg"></div>
                      @if($unit->photo_path)
                          <img src="{{ Storage::url($unit->photo_path) }}" alt="Foto de {{ $unit->name }}" class="w-full h-48 object-cover">
                      @endif
                      <div class="p-6 flex-1">
                          <div class="flex items-start justify-between">
                              <span class="bg-teal-50 text-teal-700 text-[10px] font-bold uppercase tracking-wider py-1 px-2 rounded border border-teal-100">
                                  {{ $unit->kind ?? ''Unidade Básica'' }}
                              </span>
                          </div>
                          <h4 class="text-xl font-bold font-sora text-gray-800 mt-3 mb-1">{{ $unit->name }}</h4>
                          @if($unit->description)
                              <p class="text-sm text-gray-500 mt-2 mb-4 line-clamp-2" title="{{ $unit->description }}">{{ $unit->description }}</p>
                          @endif
                          
                          <div class="mt-4 space-y-3">
                              <div class="flex items-start text-sm text-gray-600">
                                  <svg class="w-5 h-5 text-[var(--gov-primary)] mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                  <span>{{ $unit->address ?: ''Endereço não informado'' }}</span>
                              </div>
                              <div class="flex items-start text-sm text-gray-600">
                                  <svg class="w-5 h-5 text-[var(--gov-primary)] mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                  <span>{{ $unit->phone ?: ''Telefone Indisponível'' }}</span>
                              </div>
                              <div class="flex items-start text-sm text-gray-600">
                                  <svg class="w-5 h-5 text-[var(--gov-primary)] mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                  <span>Horário Oficial: 08:00 às 17:00</span>
                              </div>
                          </div>
                      </div>
                      <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 w-full text-center">
                          <a href="{{ $unit->maps_link ?: ''https://maps.google.com/?q='' . urlencode($unit->address . '', Assaí - PR'') }}" target="_blank" rel="noopener noreferrer" class="text-sm font-bold text-[var(--gov-primary)] hover:text-[var(--gov-primary-dark)]">Ver no Mapa &rarr;</a>
                      </div>
                  </div>'
$content = $content -replace $cardRegex, $newCard

$content = $content -replace '<a href="#" class="inline-flex justify-center px-6 py-3\s*border border-gray-300 shadow-sm text-base font-medium rounded-md\s*text-gray-700 bg-white hover:bg-gray-50 transition-colors">\s*Consultar todas as unidades\s*</a>', '<a href="{{ route(''portal.units'') }}" class="inline-flex justify-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">Consultar todas as unidades</a>'

Set-Content $file -Value $content -Encoding UTF8
