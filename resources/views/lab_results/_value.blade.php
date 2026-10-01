{{-- One analyte of a lab result, highlighted when outside the reference range. --}}
@php($v = $result->{$field})
@if($v === null)
  —
@else
  <span @class(['text-danger font-weight-bold' => $result->isOutOfRange($field)])>{{ rtrim(rtrim(number_format($v, 1, ',', '.'), '0'), ',') }}</span>
@endif
