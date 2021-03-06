<?php


namespace KDuma\ContentNegotiableResponses;


use KDuma\ContentNegotiableResponses\Interfaces\MsgPackResponseInterface;
use KDuma\ContentNegotiableResponses\Interfaces\JsonResponseInterface;
use KDuma\ContentNegotiableResponses\Interfaces\TextResponseInterface;
use KDuma\ContentNegotiableResponses\Interfaces\YamlResponseInterface;
use KDuma\ContentNegotiableResponses\Traits\DiscoversPublicProperties;
use KDuma\ContentNegotiableResponses\Interfaces\XmlResponseInterface;
use KDuma\ContentNegotiableResponses\Helpers\ResourceResponseHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Spatie\ArrayToXml\ArrayToXml;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use MessagePack\MessagePack;
use JsonSerializable;

abstract class BaseArrayResponse extends BaseResponse
    implements JsonResponseInterface, XmlResponseInterface, MsgPackResponseInterface, YamlResponseInterface
{
    use DiscoversPublicProperties;
    
    /**
     * @return Collection
     * @throws \ReflectionException
     */
    protected function getData()
    {
        return $this->getPublicProperties();
    }

    /**
     * @param Request $request
     *
     * @return array
     * @throws \ReflectionException
     */
    protected function getDataArray($request)
    {
        $data = $this->getData();

        if ($data instanceof JsonResource) {
            $helper = new ResourceResponseHelper($data);
            $data = $helper->getData($request);
            
            if(!$this->responseCode)
                $this->responseCode = $helper->getStatusCode();
        } 
        elseif ($data instanceof Arrayable) {
            $data = $data->toArray($request);
        } 
        elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }
        
        return $data;
    }

    /**
     * @return string
     */
    protected function getDefaultType(): string
    {
        return JsonResponseInterface::class;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function toTextResponse($request)
    {
        $content = print_r($this->getDataArray($request), true);

        return \response($content)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function toJsonResponse($request)
    {
        $content = json_encode($this->getDataArray($request), JSON_PRETTY_PRINT);

        return \response($content)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws \DOMException
     */
    public function toXmlResponse($request)
    {
        $converter = new ArrayToXml($this->getDataArray($request));
        $dom = $converter->toDom();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $content = $dom->saveXML();

        return \response($content)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function toYamlResponse($request)
    {
        $content = $yaml = Yaml::dump($this->getDataArray($request), 2, 4);

        return \response($content)->header('Content-Type', 'application/yaml; charset=UTF-8');
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function toMsgPackResponse($request)
    {
        $content = MessagePack::pack($this->getDataArray($request));

        return \response($content)->header('Content-Type', 'application/msgpack');
    }
}
